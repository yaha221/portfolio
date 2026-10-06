<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Entity\Technology;

final readonly class AdminTechnologyView
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $icon,
    ) {
    }

    public static function fromEntity(Technology $technology): self
    {
        return new self((int) $technology->getId(), $technology->getName(), $technology->getSlug(), $technology->getIcon());
    }
}
