<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\PageView;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<PageView>
 */
final class PageViewFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return PageView::class;
    }

    protected function defaults(): array
    {
        return [
            'path' => self::faker()->randomElement(['/', '/projects', '/projects/portfolio', '/skills', '/contact']),
            'ipHash' => hash('sha256', self::faker()->ipv4()),
            'userAgent' => self::faker()->userAgent(),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-30 days')),
        ];
    }
}
