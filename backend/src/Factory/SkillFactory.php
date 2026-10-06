<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Skill;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Skill>
 */
final class SkillFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Skill::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(self::faker()->unique()->word()),
            'category' => self::faker()->randomElement(['Backend', 'Frontend', 'DevOps']),
            'level' => self::faker()->numberBetween(Skill::MIN_LEVEL, Skill::MAX_LEVEL),
        ];
    }
}
