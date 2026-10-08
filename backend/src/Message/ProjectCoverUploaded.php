<?php

declare(strict_types=1);

namespace App\Message;

/**
 * A project got a new cover; the worker makes its WebP thumbnails.
 * Carries the path, so a cover replaced again before processing is skipped.
 */
final readonly class ProjectCoverUploaded
{
    public function __construct(
        public int $projectId,
        public string $coverPath,
    ) {
    }
}
