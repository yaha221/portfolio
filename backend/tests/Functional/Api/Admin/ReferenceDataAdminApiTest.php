<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Factory\ContactMessageFactory;
use App\Factory\ExperienceFactory;
use App\Factory\ProjectFactory;
use App\Factory\SkillFactory;
use App\Factory\TechnologyFactory;
use App\Tests\Functional\Api\ApiTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;

/**
 * Technologies, skills, experience and contact messages in the admin API.
 */
#[ResetDatabase]
final class ReferenceDataAdminApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
    }

    public function testTechnologyLifecycle(): void
    {
        $created = $this->sendJson('POST', '/api/admin/technologies', ['name' => 'Symfony', 'slug' => 'symfony', 'icon' => 'symfony']);
        self::assertResponseStatusCodeSame(201);

        $updated = $this->sendJson('PUT', '/api/admin/technologies/'.$created['id'], ['name' => 'Symfony 7', 'slug' => 'symfony']);
        self::assertSame(['id' => $created['id'], 'name' => 'Symfony 7', 'slug' => 'symfony', 'icon' => null], $updated);

        $this->sendJson('DELETE', '/api/admin/technologies/'.$created['id']);
        self::assertResponseStatusCodeSame(204);
        TechnologyFactory::repository()->assert()->empty();
    }

    public function testTechnologyNameAndSlugAreUnique(): void
    {
        TechnologyFactory::createOne(['name' => 'PHP', 'slug' => 'php']);

        $this->sendJson('POST', '/api/admin/technologies', ['name' => 'PHP', 'slug' => 'php-8']);
        self::assertSame(['name'], $this->assertViolations());

        $this->sendJson('POST', '/api/admin/technologies', ['name' => 'PHP 8', 'slug' => 'php']);
        self::assertSame(['slug'], $this->assertViolations());
    }

    public function testDeletingTechnologyDetachesItFromProjects(): void
    {
        $php = TechnologyFactory::createOne(['slug' => 'php']);
        $project = ProjectFactory::createOne(['technologies' => [$php]]);

        $this->sendJson('DELETE', '/api/admin/technologies/'.$php->getId());

        self::assertResponseStatusCodeSame(204);
        $reloaded = ProjectFactory::repository()->find($project->getId());
        self::assertNotNull($reloaded);
        self::assertCount(0, $reloaded->getTechnologies());
    }

    public function testSkillLifecycleAndLevelRange(): void
    {
        $this->sendJson('POST', '/api/admin/skills', ['name' => 'PHP', 'category' => 'Backend', 'level' => 6]);
        self::assertSame(['level'], $this->assertViolations());

        $created = $this->sendJson('POST', '/api/admin/skills', ['name' => 'PHP', 'category' => 'Backend', 'level' => 5, 'sortOrder' => 1]);
        self::assertResponseStatusCodeSame(201);

        $this->sendJson('POST', '/api/admin/skills', ['name' => 'PHP', 'category' => 'Other', 'level' => 1]);
        self::assertSame(['name'], $this->assertViolations());

        $updated = $this->sendJson('PUT', '/api/admin/skills/'.$created['id'], ['name' => 'PHP', 'category' => 'Backend', 'level' => 4]);
        self::assertSame(4, $updated['level']);
        self::assertSame(0, $updated['sortOrder']);

        $this->sendJson('DELETE', '/api/admin/skills/'.$created['id']);
        self::assertResponseStatusCodeSame(204);
        SkillFactory::repository()->assert()->empty();
    }

    public function testExperienceLifecycle(): void
    {
        $created = $this->sendJson('POST', '/api/admin/experience', ['company' => 'Acme', 'position' => 'PHP-разработчик', 'startDate' => '2023-01-18']);
        self::assertResponseStatusCodeSame(201);
        self::assertTrue($created['isCurrent']);
        self::assertNull($created['endDate']);

        $updated = $this->sendJson('PUT', '/api/admin/experience/'.$created['id'], [
            'company' => 'Acme',
            'position' => 'Senior PHP-разработчик',
            'startDate' => '2023-01-18',
            'endDate' => '2025-03-01',
        ]);
        self::assertFalse($updated['isCurrent']);
        self::assertSame('2025-03-01', $updated['endDate']);

        $this->sendJson('DELETE', '/api/admin/experience/'.$created['id']);
        self::assertResponseStatusCodeSame(204);
        ExperienceFactory::repository()->assert()->empty();
    }

    public function testExperienceDatesAreValidated(): void
    {
        $this->sendJson('POST', '/api/admin/experience', ['company' => 'Acme', 'position' => 'Dev', 'startDate' => '18.01.2023']);
        $problem = $this->assertProblem(422);
        self::assertSame([['propertyPath' => 'startDate', 'message' => 'This value is not a valid date.']], $problem['violations']);

        $this->sendJson('POST', '/api/admin/experience', ['company' => 'Acme', 'position' => 'Dev', 'startDate' => '2024-05-01', 'endDate' => '2023-01-01']);
        self::assertSame(['endDate'], $this->assertViolations());
    }

    public function testMessagesArePaginatedNewestFirst(): void
    {
        $messages = ContactMessageFactory::createMany(3);

        $data = $this->getJson('/api/admin/messages?perPage=2');

        self::assertSame([$messages[2]->getId(), $messages[1]->getId()], array_column($data['items'], 'id'));
        self::assertSame('new', $data['items'][0]['status']);
        self::assertSame(['page' => 1, 'perPage' => 2, 'total' => 3, 'pages' => 2], $data['meta']);
    }
}
