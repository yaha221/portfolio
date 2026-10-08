<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Project;
use App\Message\ProjectCoverUploaded;
use App\MessageHandler\GenerateProjectCoverThumbnails;
use App\Repository\ProjectRepository;
use App\Service\Image\ThumbnailGenerator;
use App\Service\UploadStorage;
use App\Tests\Support\TestImage;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class GenerateProjectCoverThumbnailsTest extends TestCase
{
    private Filesystem $storage;

    protected function setUp(): void
    {
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testStoresCoverThumbnailsAndRemovesStaleOnes(): void
    {
        // A redelivered message: "small" exists already and is overwritten, "tiny" is from an old size set
        $project = $this->projectWithCover('projects/cover.png');
        $project->setCoverThumbnails(['small' => 'projects/cover-small.webp', 'tiny' => 'projects/cover-tiny.webp']);
        $this->storage->write('projects/cover-small.webp', 'old');
        $this->storage->write('projects/cover-tiny.webp', 'old');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $this->handler($project, $entityManager)(new ProjectCoverUploaded(5, 'projects/cover.png'));

        self::assertSame(['small' => 'projects/cover-small.webp', 'large' => 'projects/cover-large.webp'], $project->getCoverThumbnails());
        self::assertNotSame('old', $this->storage->read('projects/cover-small.webp'));
        self::assertTrue($this->storage->fileExists('projects/cover-large.webp'));
        self::assertFalse($this->storage->fileExists('projects/cover-tiny.webp'));
    }

    public function testCoverReplacedBeforeTheWorkerRanIsSkipped(): void
    {
        // The message is about the old cover; the new one has its own message on the way
        $project = $this->projectWithCover('projects/new.png');
        $this->storage->write('projects/old.png', TestImage::png(800, 400));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $this->handler($project, $entityManager)(new ProjectCoverUploaded(5, 'projects/old.png'));

        self::assertSame([], $project->getCoverThumbnails());
        self::assertFalse($this->storage->fileExists('projects/old-small.webp'));
        self::assertFalse($this->storage->fileExists('projects/new-small.webp'));
    }

    public function testDeletedProjectIsSkipped(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $this->handler(null, $entityManager)(new ProjectCoverUploaded(5, 'projects/cover.png'));

        self::assertSame([], $this->storage->listContents('/', true)->toArray());
    }

    public function testBrokenCoverIsNotRetried(): void
    {
        $project = new Project('p', 'P', 'd');
        $project->setCoverImage('projects/cover.png');
        $this->storage->write('projects/cover.png', 'not an image');

        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler($project, $this->createStub(EntityManagerInterface::class))(new ProjectCoverUploaded(5, 'projects/cover.png'));
    }

    public function testMissingCoverFileIsNotRetried(): void
    {
        $project = new Project('p', 'P', 'd');
        $project->setCoverImage('projects/gone.png');

        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler($project, $this->createStub(EntityManagerInterface::class))(new ProjectCoverUploaded(5, 'projects/gone.png'));
    }

    private function projectWithCover(string $path): Project
    {
        $project = new Project('p', 'P', 'd');
        $project->setCoverImage($path);
        $this->storage->write($path, TestImage::png(1600, 900));

        return $project;
    }

    private function handler(?Project $project, EntityManagerInterface $entityManager): GenerateProjectCoverThumbnails
    {
        $projects = $this->createMock(ProjectRepository::class);
        $projects->expects(self::once())->method('find')->with(5)->willReturn($project);

        return new GenerateProjectCoverThumbnails($projects, new ThumbnailGenerator($this->storage), new UploadStorage($this->storage), $entityManager);
    }
}
