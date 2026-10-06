<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\Experience;
use OpenApi\Attributes as OA;

final readonly class ExperienceView
{
    public function __construct(
        public string $company,
        public string $position,
        #[OA\Property(format: 'date', example: '2023-01-18')]
        public string $startDate,
        #[OA\Property(format: 'date', description: 'null для текущего места работы')]
        public ?string $endDate,
        public bool $isCurrent,
        public string $description,
    ) {
    }

    public static function fromEntity(Experience $experience): self
    {
        return new self(
            $experience->getCompany(),
            $experience->getPosition(),
            $experience->getStartDate()->format('Y-m-d'),
            $experience->getEndDate()?->format('Y-m-d'),
            $experience->isCurrent(),
            $experience->getDescription(),
        );
    }
}
