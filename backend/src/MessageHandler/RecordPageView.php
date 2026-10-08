<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\PageView;
use App\Message\PageViewed;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RecordPageView
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(PageViewed $event): void
    {
        $this->entityManager->persist(new PageView($event->path, $event->visitorHash, $event->userAgent, $event->viewedAt));
        $this->entityManager->flush();
    }
}
