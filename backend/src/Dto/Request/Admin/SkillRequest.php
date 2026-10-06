<?php

declare(strict_types=1);

namespace App\Dto\Request\Admin;

use App\Entity\Skill;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SkillRequest
{
    public function __construct(
        #[OA\Property(example: 'Symfony')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,
        #[OA\Property(example: 'Backend')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public string $category,
        #[OA\Property(example: 5)]
        #[Assert\Range(min: Skill::MIN_LEVEL, max: Skill::MAX_LEVEL)]
        public int $level,
        #[Assert\PositiveOrZero]
        public int $sortOrder = 0,
    ) {
    }
}
