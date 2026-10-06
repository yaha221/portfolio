<?php

declare(strict_types=1);

namespace App\Dto\Request\Admin;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TechnologyRequest
{
    public function __construct(
        #[OA\Property(example: 'Symfony')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,
        #[OA\Property(example: 'symfony')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        #[Assert\Regex(ProjectRequest::SLUG_PATTERN, message: 'Use lowercase letters, digits and single hyphens.')]
        public string $slug,
        #[OA\Property(description: 'Slug иконки в Simple Icons', example: 'symfony')]
        #[Assert\Length(max: 100)]
        #[Assert\Regex('/^[a-z0-9]+$/', message: 'Use a Simple Icons slug: lowercase letters and digits.')]
        public ?string $icon = null,
    ) {
    }
}
