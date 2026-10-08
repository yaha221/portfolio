<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * One page of published projects in display order, with their technologies, in three cheap queries:
     * the page itself (partial index, no joins), technologies of just these projects, the total.
     * Doctrine's Paginator would instead number every published row joined with its technologies
     * (~95 ms on 20k projects vs ~1 ms, see stage 5 notes in README).
     *
     * @return array{items: list<Project>, total: int}
     */
    public function findPublishedPage(?string $technologySlug, int $page, int $perPage): array
    {
        $filtered = $this->createQueryBuilder('p')->where('p.isPublished = true');
        if (null !== $technologySlug) {
            // EXISTS rather than a join, so each project still lists all its technologies
            $filtered
                ->andWhere('EXISTS (SELECT fp.id FROM App\Entity\Project fp JOIN fp.technologies ft WHERE fp = p AND ft.slug = :technology)')
                ->setParameter('technology', $technologySlug);
        }

        $total = (int) (clone $filtered)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        /** @var list<Project> $items */
        $items = $filtered
            ->orderBy('p.sortOrder', SortDirection::Ascending)
            ->addOrderBy('p.id', SortDirection::Ascending)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        if ([] !== $items) {
            // Fetch-joining the already loaded projects fills their technology collections in one query
            $this->createQueryBuilder('p')
                ->addSelect('t')
                ->leftJoin('p.technologies', 't')
                ->where('p IN (:projects)')
                ->setParameter('projects', $items)
                ->getQuery()
                ->getResult();
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * All projects including drafts, in display order.
     *
     * @return list<Project>
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('t', 'i')
            ->leftJoin('p.technologies', 't')
            ->leftJoin('p.images', 'i')
            ->orderBy('p.sortOrder', SortDirection::Ascending)
            ->addOrderBy('p.id', SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Project
    {
        return $this->createQueryBuilder('p')
            ->addSelect('t', 'i')
            ->leftJoin('p.technologies', 't')
            ->leftJoin('p.images', 'i')
            ->where('p.slug = :slug')
            ->andWhere('p.isPublished = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
