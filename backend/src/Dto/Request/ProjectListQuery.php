<?php

declare(strict_types=1);

namespace App\Dto\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectListQuery
{
    public const int MAX_PER_PAGE = 50;

    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: self::MAX_PER_PAGE)]
        public int $perPage = 12,
        #[OA\Property(description: 'Slug технологии: вернуть только проекты с ней', example: 'symfony')]
        #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Technology must be a lowercase slug.')]
        public ?string $technology = null,
    ) {
    }
}
