<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    /**
     * Newest first.
     *
     * @return Paginator<ContactMessage>
     */
    public function findPage(int $page, int $perPage): Paginator
    {
        $query = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', SortDirection::Descending)
            ->addOrderBy('m.id', SortDirection::Descending)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        return new Paginator($query, fetchJoinCollection: false);
    }
}
