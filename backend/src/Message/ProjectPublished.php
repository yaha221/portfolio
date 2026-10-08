<?php

declare(strict_types=1);

namespace App\Message;

/**
 * A project went from draft to published.
 */
final readonly class ProjectPublished
{
    public function __construct(
        public int $projectId,
    ) {
    }
}
