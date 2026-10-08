<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Anonymous visitor id for analytics: a hash of IP + user agent with a salt that changes daily.
 * Counts unique visitors per day, but the IP is never stored and visitors can't be followed across days.
 */
final readonly class VisitorHasher
{
    public function __construct(
        #[Autowire(param: 'kernel.secret')]
        private string $secret,
    ) {
    }

    public function hash(string $ip, ?string $userAgent, DateTimeImmutable $day): string
    {
        return hash('sha256', implode('|', [$day->format('Y-m-d'), $this->secret, $ip, $userAgent ?? '']));
    }
}
