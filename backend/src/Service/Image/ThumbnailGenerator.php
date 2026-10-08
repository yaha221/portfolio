<?php

declare(strict_types=1);

namespace App\Service\Image;

use GdImage;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use RuntimeException;

/**
 * Makes WebP thumbnails next to an uploaded image: "projects/abc.jpg" -> "projects/abc-small.webp", ...
 */
final readonly class ThumbnailGenerator
{
    // Thumbnail name => maximum width in pixels; smaller originals are not upscaled
    public const array SIZES = ['small' => 480, 'large' => 1280];

    private const int WEBP_QUALITY = 82;

    public function __construct(
        private FilesystemOperator $uploadsStorage,
    ) {
    }

    /**
     * @return array<string, string> thumbnail name => path in the uploads storage
     *
     * @throws UnableToReadFile         when the original is missing from the storage
     * @throws InvalidArgumentException when the file is not a readable image
     */
    public function generate(string $originalPath): array
    {
        $bytes = $this->uploadsStorage->read($originalPath);
        $image = @imagecreatefromstring($bytes);
        if (false === $image) {
            throw new InvalidArgumentException(\sprintf('"%s" is not an image GD can read.', $originalPath));
        }
        $image = self::applyExifOrientation($image, $bytes);

        $base = (string) preg_replace('/\.[^.\/]+$/', '', $originalPath);
        $thumbnails = [];
        foreach (self::SIZES as $name => $maxWidth) {
            $path = \sprintf('%s-%s.webp', $base, $name);
            $this->uploadsStorage->write($path, self::toWebp(self::fitWidth($image, $maxWidth)));
            $thumbnails[$name] = $path;
        }

        return $thumbnails;
    }

    private static function fitWidth(GdImage $image, int $maxWidth): GdImage
    {
        if (imagesx($image) <= $maxWidth) {
            return $image;
        }

        // Height -1 keeps the aspect ratio
        $scaled = imagescale($image, $maxWidth, -1, \IMG_BICUBIC);
        if (false === $scaled) {
            throw new RuntimeException('GD could not scale the image.');
        }

        return $scaled;
    }

    private static function toWebp(GdImage $image): string
    {
        // Keep PNG transparency
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $written = imagewebp($image, null, self::WEBP_QUALITY);
        $webp = (string) ob_get_clean();
        if (!$written || '' === $webp) {
            throw new RuntimeException('GD could not encode WebP.');
        }

        return $webp;
    }

    /**
     * Phone cameras store portrait photos sideways plus an EXIF "Orientation" flag; GD ignores the flag.
     */
    private static function applyExifOrientation(GdImage $image, string $bytes): GdImage
    {
        if (!str_starts_with($bytes, "\xFF\xD8")) {
            return $image; // EXIF orientation only matters for JPEG
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $angle = match (\is_array($exif) ? ($exif['Orientation'] ?? 1) : 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if (0 === $angle) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return false === $rotated ? $image : $rotated;
    }
}
