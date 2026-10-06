<?php

declare(strict_types=1);

namespace App\Dto\Request\Admin;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Full project state for POST and PUT. Images and the cover have their own endpoints.
 */
final readonly class ProjectRequest
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param list<string> $technologies
     */
    public function __construct(
        #[OA\Property(example: 'portfolio')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Regex(self::SLUG_PATTERN, message: 'Use lowercase letters, digits and single hyphens.')]
        public string $slug,
        #[OA\Property(example: 'Портфолио')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public string $description,
        #[OA\Property(description: 'Полное описание в Markdown')]
        public string $content = '',
        #[Assert\Length(max: 255)]
        #[Assert\Url(requireTld: true)]
        public ?string $repoUrl = null,
        #[Assert\Length(max: 255)]
        #[Assert\Url(requireTld: true)]
        public ?string $demoUrl = null,
        public bool $isPublished = false,
        #[Assert\PositiveOrZero]
        public int $sortOrder = 0,
        #[OA\Property(description: 'Slug-и существующих технологий', example: ['php', 'symfony'])]
        #[Assert\Unique]
        #[Assert\Count(max: 30)]
        #[Assert\All([new Assert\Regex(self::SLUG_PATTERN)])]
        public array $technologies = [],
    ) {
    }
}
