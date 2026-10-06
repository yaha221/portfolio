<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\Skill;
use OpenApi\Attributes as OA;

final readonly class SkillView
{
    public function __construct(
        #[OA\Property(example: 'Symfony')]
        public string $name,
        #[OA\Property(example: 'Backend')]
        public string $category,
        #[OA\Property(minimum: Skill::MIN_LEVEL, maximum: Skill::MAX_LEVEL, example: 5)]
        public int $level,
    ) {
    }

    public static function fromEntity(Skill $skill): self
    {
        return new self($skill->getName(), $skill->getCategory(), $skill->getLevel());
    }
}
