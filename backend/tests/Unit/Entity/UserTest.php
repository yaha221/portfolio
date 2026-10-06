<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRolesAlwaysContainRoleUserOnce(): void
    {
        $user = new User('admin@example.com');
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
