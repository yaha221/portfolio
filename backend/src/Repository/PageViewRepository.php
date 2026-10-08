<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PageView;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<PageView>
 */
class PageViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageView::class);
    }

    /**
     * Views and unique visitors per UTC day, days without views included as zeros.
     * The visitor hash already contains the day, so DISTINCT counts unique visitors of that day.
     *
     * @return list<array{day: string, views: int, visitors: int}>
     */
    public function dailyStats(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT to_char(d, 'YYYY-MM-DD') AS day, COUNT(v.id) AS views, COUNT(DISTINCT v.ip_hash) AS visitors
             FROM generate_series(CAST(:from AS date), CAST(:to AS date), interval '1 day') AS d
             LEFT JOIN page_view v ON v.created_at >= d AND v.created_at < d + interval '1 day'
             GROUP BY d
             ORDER BY d",
            ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')],
        );

        return array_map(static fn (array $row) => [
            'day' => (string) $row['day'],
            'views' => (int) $row['views'],
            'visitors' => (int) $row['visitors'],
        ], $rows);
    }

    /**
     * @return list<array{path: string, views: int}>
     */
    public function topPaths(DateTimeImmutable $since, int $limit): array
    {
        $rows = $this->createQueryBuilder('v')
            ->select('v.path AS path', 'COUNT(v.id) AS views')
            ->where('v.createdAt >= :since')
            ->groupBy('v.path')
            ->orderBy('views', SortDirection::Descending)
            ->addOrderBy('v.path', SortDirection::Ascending)
            ->setParameter('since', $since)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row) => ['path' => (string) $row['path'], 'views' => (int) $row['views']], $rows);
    }
}
