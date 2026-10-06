<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Technology;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Technology>
 */
final class TechnologyFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Technology::class;
    }

    protected function defaults(): array
    {
        $slug = self::faker()->unique()->slug(1);

        return [
            'name' => ucfirst($slug),
            'slug' => $slug,
        ];
    }
}
