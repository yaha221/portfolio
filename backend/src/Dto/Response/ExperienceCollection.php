<?php

declare(strict_types=1);

namespace App\Dto\Response;

final readonly class ExperienceCollection
{
    /**
     * @param list<ExperienceView> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
