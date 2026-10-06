<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\ContactMessage;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ContactMessage>
 */
final class ContactMessageFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ContactMessage::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->name(),
            'email' => self::faker()->safeEmail(),
            'message' => self::faker()->realText(300),
        ];
    }
}
