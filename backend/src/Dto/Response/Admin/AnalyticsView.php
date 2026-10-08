<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use OpenApi\Attributes as OA;

final readonly class AnalyticsView
{
    /**
     * @param list<DailyStat> $days
     * @param list<PathStat>  $topPaths
     */
    public function __construct(
        #[OA\Property(format: 'date')]
        public string $from,
        #[OA\Property(format: 'date')]
        public string $to,
        public int $totalViews,
        #[OA\Property(description: 'Сумма дневных уникальных посетителей: один человек в разные дни считается заново')]
        public int $totalVisitors,
        #[OA\Property(description: 'Каждый день периода, включая дни без просмотров')]
        public array $days,
        #[OA\Property(description: 'До 10 самых просматриваемых страниц за период')]
        public array $topPaths,
    ) {
    }
}
