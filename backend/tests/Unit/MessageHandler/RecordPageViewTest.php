<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\PageView;
use App\Message\PageViewed;
use App\MessageHandler\RecordPageView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RecordPageViewTest extends TestCase
{
    public function testStoresTheViewWithTheTimeItHappenedNotWhenTheWorkerRan(): void
    {
        $viewedAt = new DateTimeImmutable('2026-10-01 12:00:00');
        $stored = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$stored): void {
                $stored = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        new RecordPageView($entityManager)(new PageViewed('/projects', 'visitor-hash', 'Firefox', $viewedAt));

        self::assertInstanceOf(PageView::class, $stored);
        self::assertSame('/projects', $stored->getPath());
        self::assertSame('visitor-hash', $stored->getIpHash());
        self::assertSame('Firefox', $stored->getUserAgent());
        self::assertEquals($viewedAt, $stored->getCreatedAt());
    }
}
