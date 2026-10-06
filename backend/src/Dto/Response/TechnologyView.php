<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

/**
 * Technology for the filter on the projects page.
 */
final readonly class TechnologyView
{
    public function __construct(
        #[OA\Property(example: 'symfony')]
        public string $slug,
        #[OA\Property(example: 'Symfony')]
        public string $name,
        #[OA\Property(description: 'Slug иконки в Simple Icons', example: 'symfony')]
        public ?string $icon,
        #[OA\Property(description: 'Сколько опубликованных проектов её используют', example: 3)]
        public int $projectCount,
    ) {
    }
}
