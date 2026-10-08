<?php

declare(strict_types=1);

namespace App\Dto\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AnalyticsQuery
{
    public function __construct(
        #[OA\Property(description: 'За сколько последних дней, включая сегодня')]
        #[Assert\Range(min: 1, max: 365)]
        public int $days = 30,
    ) {
    }
}
