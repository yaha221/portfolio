<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\Image\ThumbnailGenerator;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Runs against the in-memory uploads storage of the test environment.
 */
final class ThumbnailGeneratorTest extends KernelTestCase
{
    public function testMakesWebpThumbnailsKeepingAspectRatio(): void
    {
        $this->storage()->write('projects/wide.png', self::image(2000, 1000, 'png'));

        $thumbnails = $this->generator()->generate('projects/wide.png');

        self::assertSame(['small' => 'projects/wide-small.webp', 'large' => 'projects/wide-large.webp'], $thumbnails);
        self::assertSame([480, 240, 'image/webp'], $this->sizeOf($thumbnails['small']));
        self::assertSame([1280, 640, 'image/webp'], $this->sizeOf($thumbnails['large']));
    }

    public function testDoesNotUpscaleSmallImages(): void
    {
        $this->storage()->write('projects/tiny.jpg', self::image(300, 150, 'jpeg'));

        $thumbnails = $this->generator()->generate('projects/tiny.jpg');

        self::assertSame([300, 150, 'image/webp'], $this->sizeOf($thumbnails['small']));
        self::assertSame([300, 150, 'image/webp'], $this->sizeOf($thumbnails['large']));
    }

    public function testRotatesPhotoByExifOrientation(): void
    {
        // Landscape pixels with Orientation = 6: a phone portrait photo stored sideways
        $this->storage()->write('projects/phone.jpg', self::withExifOrientation(self::image(200, 100, 'jpeg'), 6));

        $thumbnails = $this->generator()->generate('projects/phone.jpg');

        self::assertSame([100, 200, 'image/webp'], $this->sizeOf($thumbnails['small']));
    }

    public function testRejectsFileThatIsNotAnImage(): void
    {
        $this->storage()->write('projects/notes.jpg', 'plain text');

        $this->expectException(InvalidArgumentException::class);

        $this->generator()->generate('projects/notes.jpg');
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private static function image(int $width, int $height, string $format): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 30, 120, 200));

        ob_start();
        'png' === $format ? imagepng($image) : imagejpeg($image);

        return (string) ob_get_clean();
    }

    /**
     * Inserts a minimal EXIF (APP1) segment with one Orientation tag right after the JPEG SOI marker.
     */
    private static function withExifOrientation(string $jpeg, int $orientation): string
    {
        // Little-endian TIFF header, IFD0 at offset 8 with one entry: tag 0x0112 (Orientation), SHORT, count 1
        $tiff = "II*\x00\x08\x00\x00\x00"
            ."\x01\x00"
            ."\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientation)."\x00\x00"
            ."\x00\x00\x00\x00";
        $app1 = "Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', \strlen($app1) + 2).$app1.substr($jpeg, 2);
    }

    /**
     * @return array{int, int, string}
     */
    private function sizeOf(string $path): array
    {
        $info = getimagesizefromstring($this->storage()->read($path));
        self::assertIsArray($info);

        return [$info[0], $info[1], $info['mime']];
    }

    private function generator(): ThumbnailGenerator
    {
        return self::getContainer()->get(ThumbnailGenerator::class);
    }

    private function storage(): FilesystemOperator
    {
        $storage = self::getContainer()->get('uploads.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        return $storage;
    }
}
