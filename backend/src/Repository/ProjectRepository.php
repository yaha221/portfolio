<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     * Published projects in display order, technologies fetched in the same query.
     *
     * @return Paginator<Project>
     */
    public function findPublishedPage(?string $technologySlug, int $page, int $perPage): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('t')
            ->leftJoin('p.technologies', 't')
            ->where('p.isPublished = true')
            ->orderBy('p.sortOrder', SortDirection::Ascending)
            ->addOrderBy('p.id', SortDirection::Ascending)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if (null !== $technologySlug) {
            // Subquery instead of filtering the join, so each project still lists all its technologies
            $withTechnology = $this->createQueryBuilder('fp')
                ->select('fp.id')
                ->join('fp.technologies', 'ft')
                ->where('ft.slug = :technology');

            $qb->andWhere($qb->expr()->in('p.id', $withTechnology->getDQL()))
                ->setParameter('technology', $technologySlug);
        }

        return new Paginator($qb->getQuery(), fetchJoinCollection: true);
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
