<?php

declare(strict_types=1);

namespace App\Tests\Support;

use GdImage;
use PHPUnit\Framework\Assert;

/**
 * Generated images for upload and thumbnail tests: blue, with an optional red top-left quarter
 * that shows where the image's corner ended up after rotation.
 */
final class TestImage
{
    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    public static function png(int $width, int $height, bool $markTopLeft = false): string
    {
        ob_start();
        imagepng(self::draw($width, $height, $markTopLeft));

        return (string) ob_get_clean();
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    public static function jpeg(int $width, int $height, bool $markTopLeft = false): string
    {
        ob_start();
        imagejpeg(self::draw($width, $height, $markTopLeft));

        return (string) ob_get_clean();
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    public static function transparentPng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * Inserts a minimal EXIF (APP1) segment with one Orientation tag right after the JPEG SOI marker.
     */
    public static function withExifOrientation(string $jpeg, int $orientation): string
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
     * Which quarter of the image is red: "top-left", "top-right", "bottom-left" or "bottom-right".
     */
    public static function redQuarter(string $bytes): string
    {
        $image = imagecreatefromstring($bytes);
        Assert::assertInstanceOf(GdImage::class, $image);
        $width = imagesx($image);
        $height = imagesy($image);

        $quarters = [
            'top-left' => [$width / 4, $height / 4],
            'top-right' => [$width * 3 / 4, $height / 4],
            'bottom-left' => [$width / 4, $height * 3 / 4],
            'bottom-right' => [$width * 3 / 4, $height * 3 / 4],
        ];
        $red = [];
        foreach ($quarters as $name => [$x, $y]) {
            $rgb = (int) imagecolorat($image, (int) $x, (int) $y);
            // Lossy formats shift colours a little: compare channels instead of exact values
            if (($rgb >> 16 & 0xFF) > ($rgb & 0xFF)) {
                $red[] = $name;
            }
        }
        Assert::assertCount(1, $red, 'Exactly one quarter of the image must be red.');

        return $red[0];
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private static function draw(int $width, int $height, bool $markTopLeft): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 30, 120, 200));
        if ($markTopLeft) {
            imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, intdiv($height, 2) - 1, (int) imagecolorallocate($image, 220, 30, 30));
        }

        return $image;
    }
}
