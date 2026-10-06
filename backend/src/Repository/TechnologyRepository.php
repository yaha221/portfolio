<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Technology;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Technology>
 */
class TechnologyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Technology::class);
    }

    /**
     * Technologies used by at least one published project, by name.
     *
     * @return list<array{slug: string, name: string, icon: string|null, projectCount: int}>
     */
    public function findUsedByPublishedProjects(): array
    {
        $rows = $this->getEntityManager()->createQuery(
            'SELECT t.slug, t.name, t.icon, COUNT(p.id) AS projectCount
             FROM App\Entity\Project p
             JOIN p.technologies t
             WHERE p.isPublished = true
             GROUP BY t.id, t.slug, t.name, t.icon
             ORDER BY t.name ASC',
        )->getResult();

        return array_map(static fn (array $row) => [
            'slug' => $row['slug'],
            'name' => $row['name'],
            'icon' => $row['icon'],
            'projectCount' => (int) $row['projectCount'],
        ], $rows);
    }
}
