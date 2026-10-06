<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Entity\Skill;

final readonly class AdminSkillView
{
    public function __construct(
        public int $id,
        public string $name,
        public string $category,
        public int $level,
        public int $sortOrder,
    ) {
    }

    public static function fromEntity(Skill $skill): self
    {
        return new self((int) $skill->getId(), $skill->getName(), $skill->getCategory(), $skill->getLevel(), $skill->getSortOrder());
    }
}
