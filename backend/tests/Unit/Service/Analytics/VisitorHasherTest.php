<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Analytics;

use App\Service\Analytics\VisitorHasher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VisitorHasherTest extends TestCase
{
    public function testSameVisitorOnTheSameDayGetsTheSameId(): void
    {
        $hasher = new VisitorHasher('secret');

        self::assertSame(
            $hasher->hash('203.0.113.7', 'Firefox', new DateTimeImmutable('2026-10-08 09:00')),
            $hasher->hash('203.0.113.7', 'Firefox', new DateTimeImmutable('2026-10-08 23:59')),
        );
    }

    public function testIdChangesTheNextDaySoVisitorsCannotBeFollowed(): void
    {
        $hasher = new VisitorHasher('secret');

        self::assertNotSame(
            $hasher->hash('203.0.113.7', 'Firefox', new DateTimeImmutable('2026-10-08')),
            $hasher->hash('203.0.113.7', 'Firefox', new DateTimeImmutable('2026-10-09')),
        );
    }

    public function testDifferentBrowsersBehindOneAddressAreDifferentVisitors(): void
    {
        $hasher = new VisitorHasher('secret');
        $day = new DateTimeImmutable('2026-10-08');

        self::assertNotSame($hasher->hash('203.0.113.7', 'Firefox', $day), $hasher->hash('203.0.113.7', 'Chrome', $day));
        self::assertNotSame($hasher->hash('203.0.113.7', 'Firefox', $day), $hasher->hash('203.0.113.7', null, $day));
    }

    public function testIdDependsOnTheSecretAndDoesNotContainTheAddress(): void
    {
        // Without the secret a known IP could be checked against stored ids by hashing it the same way
        $day = new DateTimeImmutable('2026-10-08');
        $hash = new VisitorHasher('secret')->hash('203.0.113.7', 'Firefox', $day);

        self::assertNotSame($hash, new VisitorHasher('other')->hash('203.0.113.7', 'Firefox', $day));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }
}
