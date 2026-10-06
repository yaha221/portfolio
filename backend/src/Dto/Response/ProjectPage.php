<?php

declare(strict_types=1);

namespace App\Dto\Response;

final readonly class ProjectPage
{
    /**
     * @param list<ProjectListItem> $items
     */
    public function __construct(
        public array $items,
        public PaginationMeta $meta,
    ) {
    }
}
