<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Experience;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Experience>
 */
final class ExperienceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Experience::class;
    }

    public function current(): self
    {
        return $this->with(['endDate' => null]);
    }

    protected function defaults(): array
    {
        $start = DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-10 years', '-2 years'));

        return [
            'company' => self::faker()->company(),
            'position' => self::faker()->jobTitle(),
            'startDate' => $start,
            'endDate' => $start->modify('+'.self::faker()->numberBetween(8, 36).' months'),
            'description' => self::faker()->realText(400),
        ];
    }
}
