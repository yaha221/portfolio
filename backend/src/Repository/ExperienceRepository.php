<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Experience;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Experience>
 */
class ExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Experience::class);
    }

    /**
     * Current job first, then by most recent end date.
     *
     * @return list<Experience>
     */
    public function findAllOrdered(): array
    {
        // PostgreSQL sorts NULLs first in DESC order, so endDate = null (current job) leads
        return $this->findBy([], ['endDate' => 'DESC', 'startDate' => 'DESC']);
    }
}
