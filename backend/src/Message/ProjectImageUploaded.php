<?php

declare(strict_types=1);

namespace App\Message;

/**
 * A gallery image was uploaded; the worker makes its WebP thumbnails.
 */
final readonly class ProjectImageUploaded
{
    public function __construct(
        public int $imageId,
    ) {
    }
}
