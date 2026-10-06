<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Entity\Experience;
use OpenApi\Attributes as OA;

final readonly class AdminExperienceView
{
    public function __construct(
        public int $id,
        public string $company,
        public string $position,
        #[OA\Property(format: 'date')]
        public string $startDate,
        #[OA\Property(format: 'date')]
        public ?string $endDate,
        public bool $isCurrent,
        public string $description,
    ) {
    }

    public static function fromEntity(Experience $experience): self
    {
        return new self(
            (int) $experience->getId(),
            $experience->getCompany(),
            $experience->getPosition(),
            $experience->getStartDate()->format('Y-m-d'),
            $experience->getEndDate()?->format('Y-m-d'),
            $experience->isCurrent(),
            $experience->getDescription(),
        );
    }
}
