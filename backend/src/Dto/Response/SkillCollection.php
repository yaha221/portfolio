<?php

declare(strict_types=1);

namespace App\Dto\Response;

final readonly class SkillCollection
{
    /**
     * @param list<SkillView> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
