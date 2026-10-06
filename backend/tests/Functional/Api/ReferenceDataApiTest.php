<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Factory\ExperienceFactory;
use App\Factory\ProjectFactory;
use App\Factory\SkillFactory;
use App\Factory\TechnologyFactory;
use DateTimeImmutable;
use Zenstruck\Foundry\Attribute\ResetDatabase;

/**
 * Technologies, skills and experience: the small read-only lists.
 */
#[ResetDatabase]
final class ReferenceDataApiTest extends ApiTestCase
{
    public function testTechnologiesCountOnlyPublishedProjects(): void
    {
        $php = TechnologyFactory::createOne(['name' => 'PHP', 'slug' => 'php', 'icon' => 'php']);
        $docker = TechnologyFactory::createOne(['name' => 'Docker', 'slug' => 'docker']);
        TechnologyFactory::createOne(['name' => 'Unused', 'slug' => 'unused']);
        ProjectFactory::new()->published()->many(2)->create(['technologies' => [$php]]);
        ProjectFactory::new()->published()->create(['technologies' => [$php, $docker]]);
        ProjectFactory::createOne(['technologies' => [$docker]]);

        $data = $this->getJson('/api/technologies');

        self::assertSame([
            ['slug' => 'docker', 'name' => 'Docker', 'icon' => null, 'projectCount' => 1],
            ['slug' => 'php', 'name' => 'PHP', 'icon' => 'php', 'projectCount' => 3],
        ], $data['items']);
    }

    public function testSkillsFollowSortOrder(): void
    {
        SkillFactory::createOne(['name' => 'Docker', 'category' => 'DevOps', 'level' => 3, 'sortOrder' => 2]);
        SkillFactory::createOne(['name' => 'PHP', 'category' => 'Backend', 'level' => 5, 'sortOrder' => 1]);

        $data = $this->getJson('/api/skills');

        self::assertSame([
            ['name' => 'PHP', 'category' => 'Backend', 'level' => 5],
            ['name' => 'Docker', 'category' => 'DevOps', 'level' => 3],
        ], $data['items']);
    }

    public function testCurrentJobComesFirst(): void
    {
        ExperienceFactory::createOne(['company' => 'Recent past', 'startDate' => new DateTimeImmutable('2021-01-01'), 'endDate' => new DateTimeImmutable('2023-06-01')]);
        ExperienceFactory::createOne(['company' => 'Current', 'startDate' => new DateTimeImmutable('2019-01-01'), 'endDate' => null]);
        ExperienceFactory::createOne(['company' => 'Old', 'startDate' => new DateTimeImmutable('2015-01-01'), 'endDate' => new DateTimeImmutable('2018-12-01')]);

        $data = $this->getJson('/api/experience');

        self::assertSame(['Current', 'Recent past', 'Old'], array_column($data['items'], 'company'));
        self::assertTrue($data['items'][0]['isCurrent']);
        self::assertNull($data['items'][0]['endDate']);
        self::assertSame('2021-01-01', $data['items'][1]['startDate']);
    }
}
