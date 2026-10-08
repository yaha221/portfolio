<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use OpenApi\Attributes as OA;

final readonly class DailyStat
{
    public function __construct(
        #[OA\Property(format: 'date', example: '2026-10-08')]
        public string $date,
        #[OA\Property(example: 42)]
        public int $views,
        #[OA\Property(description: 'Уникальные посетители за этот день', example: 17)]
        public int $visitors,
    ) {
    }
}
