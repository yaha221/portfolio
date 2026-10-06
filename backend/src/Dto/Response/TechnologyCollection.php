<?php

declare(strict_types=1);

namespace App\Dto\Response;

final readonly class TechnologyCollection
{
    /**
     * @param list<TechnologyView> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
