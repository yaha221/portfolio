<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;

/**
 * The SPA showed a page. Carries everything needed, so the request never touches the database.
 */
final readonly class PageViewed
{
    public function __construct(
        public string $path,
        public string $visitorHash,
        public ?string $userAgent,
        public DateTimeImmutable $viewedAt,
    ) {
    }
}
