<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Project;
use App\Factory\ProjectFactory;
use App\Factory\TechnologyFactory;
use PHPUnit\Framework\Attributes\TestWith;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class ProjectApiTest extends ApiTestCase
{
    public function testListsOnlyPublishedProjectsInSortOrder(): void
    {
        ProjectFactory::new()->published()->create(['slug' => 'second', 'sortOrder' => 2]);
        ProjectFactory::new()->published()->create(['slug' => 'first', 'sortOrder' => 1]);
        ProjectFactory::createOne(['slug' => 'draft', 'sortOrder' => 0]);

        $data = $this->getJson('/api/projects');

        self::assertSame(['first', 'second'], array_column($data['items'], 'slug'));
        self::assertSame(['page' => 1, 'perPage' => 12, 'total' => 2, 'pages' => 1], $data['meta']);
    }

    public function testPaginates(): void
    {
        foreach (range(1, 5) as $position) {
            ProjectFactory::new()->published()->create(['slug' => 'p'.$position, 'sortOrder' => $position]);
        }

        $data = $this->getJson('/api/projects?page=2&perPage=2');

        self::assertSame(['p3', 'p4'], array_column($data['items'], 'slug'));
        self::assertSame(['page' => 2, 'perPage' => 2, 'total' => 5, 'pages' => 3], $data['meta']);
    }

    public function testFilterByTechnologyKeepsAllTechnologiesOfProject(): void
    {
        $php = TechnologyFactory::createOne(['name' => 'PHP', 'slug' => 'php']);
        $vue = TechnologyFactory::createOne(['name' => 'Vue', 'slug' => 'vue']);
        ProjectFactory::new()->published()->create(['slug' => 'fullstack', 'technologies' => [$php, $vue]]);
        ProjectFactory::new()->published()->create(['slug' => 'frontend', 'technologies' => [$vue]]);

        $data = $this->getJson('/api/projects?technology=php');

        self::assertSame(['fullstack'], array_column($data['items'], 'slug'));
        self::assertSame(['php', 'vue'], array_column($data['items'][0]['technologies'], 'slug'));
        self::assertSame(1, $data['meta']['total']);
    }

    #[TestWith(['page=0', 'page'])]
    #[TestWith(['page=abc', 'page'])]
    #[TestWith(['perPage=51', 'perPage'])]
    #[TestWith(['technology=PHP%21', 'technology'])]
    public function testRejectsInvalidQuery(string $query, string $invalidField): void
    {
        $this->client->request('GET', '/api/projects?'.$query);

        $problem = $this->assertProblem(400);
        self::assertSame([$invalidField], array_column($problem['violations'], 'propertyPath'));
    }

    public function testShowsPublishedProjectWithUploadUrls(): void
    {
        ProjectFactory::new()
            ->published()
            ->afterInstantiate(static function (Project $project): void {
                $project->addImage('projects/shot.jpg')->setThumbnails(['small' => 'projects/shot-small.webp']);
            })
            ->create(['slug' => 'demo', 'coverImage' => 'projects/cover.jpg', 'content' => '## Markdown']);

        $data = $this->getJson('/api/projects/demo');

        self::assertSame('demo', $data['slug']);
        self::assertSame('## Markdown', $data['content']);
        self::assertSame('http://localhost:8080/uploads/projects/cover.jpg', $data['coverImageUrl']);
        self::assertSame([[
            'url' => 'http://localhost:8080/uploads/projects/shot.jpg',
            'thumbnails' => ['small' => 'http://localhost:8080/uploads/projects/shot-small.webp'],
        ]], $data['images']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $data['createdAt']);
    }

    public function testDraftProjectIsNotFound(): void
    {
        ProjectFactory::createOne(['slug' => 'draft']);

        $this->client->request('GET', '/api/projects/draft');

        $problem = $this->assertProblem(404);
        self::assertSame('Project "draft" not found.', $problem['detail']);
    }
}
