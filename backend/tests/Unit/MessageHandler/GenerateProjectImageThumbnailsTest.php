<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Project;
use App\Entity\ProjectImage;
use App\Message\ProjectImageUploaded;
use App\MessageHandler\GenerateProjectImageThumbnails;
use App\Repository\ProjectImageRepository;
use App\Service\Image\ThumbnailGenerator;
use App\Service\UploadStorage;
use App\Tests\Support\TestImage;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class GenerateProjectImageThumbnailsTest extends TestCase
{
    private Filesystem $storage;

    protected function setUp(): void
    {
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testStoresThumbnailsAndRemovesOnesLeftFromAnEarlierSizeSet(): void
    {
        $image = $this->imageWithOldThumbnails();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $this->handler($image, $entityManager)(new ProjectImageUploaded(9));

        self::assertSame(['small' => 'projects/shot-small.webp', 'large' => 'projects/shot-large.webp'], $image->getThumbnails());
        self::assertNotSame('old', $this->storage->read('projects/shot-small.webp'), 'Same name is overwritten in place');
        self::assertTrue($this->storage->fileExists('projects/shot-large.webp'));
        self::assertFalse($this->storage->fileExists('projects/shot-medium.webp'));
    }

    public function testKeepsOldFilesWhenTheDatabaseUpdateFails(): void
    {
        // The rows still point to the old thumbnails until the flush succeeds
        $image = $this->imageWithOldThumbnails();
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException(new RuntimeException('Database is down'));

        try {
            $this->handler($image, $entityManager)(new ProjectImageUploaded(9));
            self::fail('The database error must reach Messenger to schedule a retry.');
        } catch (RuntimeException) {
        }

        self::assertTrue($this->storage->fileExists('projects/shot-medium.webp'));
    }

    public function testDeletedImageIsSkipped(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $this->handler(null, $entityManager)(new ProjectImageUploaded(9));

        self::assertSame([], $this->storage->listContents('/', true)->toArray());
    }

    public function testBrokenFileIsNotRetried(): void
    {
        $this->storage->write('projects/shot.png', 'not an image');

        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler(new Project('p', 'P', 'd')->addImage('projects/shot.png'), $this->createStub(EntityManagerInterface::class))(new ProjectImageUploaded(9));
    }

    public function testMissingFileIsNotRetried(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler(new Project('p', 'P', 'd')->addImage('projects/gone.png'), $this->createStub(EntityManagerInterface::class))(new ProjectImageUploaded(9));
    }

    private function imageWithOldThumbnails(): ProjectImage
    {
        $image = new Project('p', 'P', 'd')->addImage('projects/shot.png');
        $image->setThumbnails(['small' => 'projects/shot-small.webp', 'medium' => 'projects/shot-medium.webp']);
        $this->storage->write('projects/shot.png', TestImage::png(2000, 1000));
        $this->storage->write('projects/shot-small.webp', 'old');
        $this->storage->write('projects/shot-medium.webp', 'old');

        return $image;
    }

    private function handler(?ProjectImage $image, EntityManagerInterface $entityManager): GenerateProjectImageThumbnails
    {
        $images = $this->createMock(ProjectImageRepository::class);
        $images->expects(self::once())->method('find')->with(9)->willReturn($image);

        return new GenerateProjectImageThumbnails($images, new ThumbnailGenerator($this->storage), new UploadStorage($this->storage), $entityManager);
    }
}
