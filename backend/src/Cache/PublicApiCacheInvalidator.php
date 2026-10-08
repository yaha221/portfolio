<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\Experience;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\Entity\Skill;
use App\Entity\Technology;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Drops cached public responses whenever something they show is written, no matter who writes it:
 * admin API, worker (thumbnails), fixtures. Tags are collected in onFlush and invalidated in
 * postFlush, i.e. after the commit, so a cache refill can't pick up the old rows.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class PublicApiCacheInvalidator implements ResetInterface
{
    private const array TAGS_BY_ENTITY = [
        // Technology counts include only published projects, so projects affect them too
        Project::class => [CacheTag::PROJECTS, CacheTag::TECHNOLOGIES],
        ProjectImage::class => [CacheTag::PROJECTS],
        // Technology names and icons are embedded in project responses
        Technology::class => [CacheTag::TECHNOLOGIES, CacheTag::PROJECTS],
        Skill::class => [CacheTag::SKILLS],
        Experience::class => [CacheTag::EXPERIENCE],
    ];

    /** @var array<string, true> */
    private array $pendingTags = [];

    public function __construct(
        #[Autowire(service: 'cache.public_api')]
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $changed = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ];
        // Many-to-many changes (project <-> technology) touch only the join table
        foreach ([...$unitOfWork->getScheduledCollectionUpdates(), ...$unitOfWork->getScheduledCollectionDeletions()] as $collection) {
            $owner = $collection->getOwner();
            if (null !== $owner) {
                $changed[] = $owner;
            }
        }

        foreach ($changed as $entity) {
            // Metadata name, not $entity::class: a lazy proxy has its own class name
            $class = $entityManager->getClassMetadata($entity::class)->getName();
            foreach (self::TAGS_BY_ENTITY[$class] ?? [] as $tag) {
                $this->pendingTags[$tag] = true;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingTags) {
            return;
        }

        $tags = array_keys($this->pendingTags);
        $this->pendingTags = [];
        $this->cache->invalidateTags($tags);
    }

    public function reset(): void
    {
        $this->pendingTags = [];
    }
}
