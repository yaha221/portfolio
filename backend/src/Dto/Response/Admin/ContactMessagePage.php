<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Dto\Response\PaginationMeta;

final readonly class ContactMessagePage
{
    /**
     * @param list<ContactMessageView> $items
     */
    public function __construct(
        public array $items,
        public PaginationMeta $meta,
    ) {
    }
}
