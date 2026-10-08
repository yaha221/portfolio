<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class UserRepositoryTest extends KernelTestCase
{
    public function testSavesARehashedPassword(): void
    {
        // Symfony calls this after a login when the hasher settings changed since the hash was made
        $user = UserFactory::createOne();

        $this->repository()->upgradePassword($user, 'new-hash');

        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertSame('new-hash', UserFactory::repository()->find($user->getId())?->getPassword());
    }

    public function testRejectsUsersFromOtherProviders(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $this->repository()->upgradePassword(new InMemoryUser('admin', 'hash'), 'new-hash');
    }

    private function repository(): UserRepository
    {
        return self::getContainer()->get(UserRepository::class);
    }
}
