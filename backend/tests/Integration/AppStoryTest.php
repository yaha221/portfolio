<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\ProjectFactory;
use App\Factory\SkillFactory;
use App\Factory\TechnologyFactory;
use App\Factory\UserFactory;
use App\Story\AppStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Attribute\ResetDatabase;

/**
 * Loads the development data set into the test database, which also proves
 * the mapping works against real PostgreSQL.
 */
#[ResetDatabase]
final class AppStoryTest extends KernelTestCase
{
    public function testLoadsDevelopmentDataSet(): void
    {
        AppStory::load();

        ProjectFactory::repository()->assert()->count(7);
        ProjectFactory::repository()->assert()->count(6, ['isPublished' => true]);
        ProjectFactory::repository()->assert()->exists(['slug' => 'draft', 'isPublished' => false]);
        TechnologyFactory::repository()->assert()->count(10);
        SkillFactory::repository()->assert()->count(8);
    }

    public function testAdminCanSignInWithFixturePassword(): void
    {
        AppStory::load();

        $admin = UserFactory::repository()->findOneBy(['email' => 'admin@portfolio.local']);
        self::assertNotNull($admin);
        self::assertContains('ROLE_ADMIN', $admin->getRoles());

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($admin, UserFactory::DEFAULT_PASSWORD));
    }
}
