<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Image;

use App\Service\Image\ThumbnailGenerator;
use App\Tests\Support\TestImage;
use GdImage;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThumbnailGeneratorTest extends TestCase
{
    private Filesystem $storage;

    protected function setUp(): void
    {
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testMakesWebpThumbnailsKeepingAspectRatio(): void
    {
        $this->storage->write('projects/wide.png', TestImage::png(2000, 1000));

        $thumbnails = $this->generator()->generate('projects/wide.png');

        self::assertSame(['small' => 'projects/wide-small.webp', 'large' => 'projects/wide-large.webp'], $thumbnails);
        self::assertSame([480, 240, 'image/webp'], $this->sizeOf($thumbnails['small']));
        self::assertSame([1280, 640, 'image/webp'], $this->sizeOf($thumbnails['large']));
    }

    public function testDoesNotUpscaleSmallImages(): void
    {
        $this->storage->write('projects/tiny.jpg', TestImage::jpeg(300, 150));

        $thumbnails = $this->generator()->generate('projects/tiny.jpg');

        self::assertSame([300, 150, 'image/webp'], $this->sizeOf($thumbnails['small']));
        self::assertSame([300, 150, 'image/webp'], $this->sizeOf($thumbnails['large']));
    }

    public function testImageExactlyAsWideAsAThumbnailIsKeptAsIs(): void
    {
        $this->storage->write('projects/exact.png', TestImage::png(480, 240));

        $thumbnails = $this->generator()->generate('projects/exact.png');

        self::assertSame([480, 240, 'image/webp'], $this->sizeOf($thumbnails['small']));
    }

    public function testKeepsTheTransparencyOfPngImages(): void
    {
        // A logo on a transparent background must not get a black box behind it
        $this->storage->write('projects/logo.png', TestImage::transparentPng(1000, 500));

        $thumbnails = $this->generator()->generate('projects/logo.png');

        foreach ($thumbnails as $path) {
            $image = imagecreatefromstring($this->storage->read($path));
            self::assertInstanceOf(GdImage::class, $image);
            $alpha = ((int) imagecolorat($image, 10, 10) >> 24) & 0x7F;
            self::assertSame(127, $alpha, $path.' must stay fully transparent');
        }
    }

    /**
     * @return iterable<string, array{int, array{int, int}, string}>
     */
    public static function exifOrientations(): iterable
    {
        // EXIF value => size of a 200×100 photo after the fix, where its top-left corner ends up
        yield 'normal' => [1, [200, 100], 'top-left'];
        yield 'upside down' => [3, [200, 100], 'bottom-right'];
        yield 'turned left, phone held upright' => [6, [100, 200], 'top-right'];
        yield 'turned right' => [8, [100, 200], 'bottom-left'];
    }

    /**
     * @param array{int, int} $size
     */
    #[DataProvider('exifOrientations')]
    public function testRotatesPhotoByExifOrientation(int $orientation, array $size, string $corner): void
    {
        $this->storage->write('projects/phone.jpg', TestImage::withExifOrientation(TestImage::jpeg(200, 100, markTopLeft: true), $orientation));

        $small = $this->generator()->generate('projects/phone.jpg')['small'];

        self::assertSame([...$size, 'image/webp'], $this->sizeOf($small));
        self::assertSame($corner, TestImage::redQuarter($this->storage->read($small)));
    }

    public function testRejectsFileThatIsNotAnImage(): void
    {
        $this->storage->write('projects/notes.jpg', 'plain text');

        $this->expectException(InvalidArgumentException::class);

        $this->generator()->generate('projects/notes.jpg');
    }

    public function testMissingOriginalIsReportedAsUnreadable(): void
    {
        $this->expectException(UnableToReadFile::class);

        $this->generator()->generate('projects/missing.jpg');
    }

    /**
     * @return array{int, int, string}
     */
    private function sizeOf(string $path): array
    {
        $info = getimagesizefromstring($this->storage->read($path));
        self::assertIsArray($info);

        return [$info[0], $info[1], $info['mime']];
    }

    private function generator(): ThumbnailGenerator
    {
        return new ThumbnailGenerator($this->storage);
    }
}
