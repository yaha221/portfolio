<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Project;
use App\Factory\ProjectFactory;
use App\Factory\TechnologyFactory;
use Zenstruck\Foundry\Attribute\ResetDatabase;

/**
 * Redis response cache, its invalidation on writes, HTTP caching, and query counts.
 */
#[ResetDatabase]
final class PublicApiCacheTest extends ApiTestCase
{
    public function testProjectListUsesConstantNumberOfQueriesAndThenNone(): void
    {
        $technologies = TechnologyFactory::createMany(3);
        ProjectFactory::new()->published()->many(10)->create(['technologies' => $technologies]);

        // page + technologies of the page + total, regardless of how many projects and technologies
        self::assertSame(3, $this->queriesOf('/api/projects'));
        self::assertCount(10, $this->decodeResponse()['items']);

        self::assertSame(0, $this->queriesOf('/api/projects'), 'second request is served from Redis');
    }

    public function testProjectDetailLoadsEverythingInOneQuery(): void
    {
        ProjectFactory::new()
            ->published()
            ->afterInstantiate(static function (Project $project): void {
                $project->addImage('projects/a.png');
                $project->addImage('projects/b.png');
            })
            ->create(['slug' => 'demo', 'technologies' => TechnologyFactory::createMany(3)]);

        self::assertSame(1, $this->queriesOf('/api/projects/demo'));
        self::assertCount(2, $this->decodeResponse()['images']);
    }

    public function testAdminChangeIsVisibleImmediately(): void
    {
        $draft = ProjectFactory::createOne(['slug' => 'draft']);
        self::assertSame([], $this->getJson('/api/projects')['items']);

        $this->loginAsAdmin();
        $this->sendJson('PATCH', '/api/admin/projects/'.$draft->getId(), ['isPublished' => true]);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');

        self::assertSame(['draft'], array_column($this->getJson('/api/projects')['items'], 'slug'));
    }

    public function testRenamingTechnologyRefreshesCachedProjects(): void
    {
        $php = TechnologyFactory::createOne(['name' => 'PHP', 'slug' => 'php']);
        ProjectFactory::new()->published()->create(['slug' => 'demo', 'technologies' => [$php]]);
        $this->getJson('/api/projects/demo');

        $this->loginAsAdmin();
        $this->sendJson('PUT', '/api/admin/technologies/'.$php->getId(), ['name' => 'PHP 8.4', 'slug' => 'php']);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');

        self::assertSame('PHP 8.4', $this->getJson('/api/projects/demo')['technologies'][0]['name']);
    }

    public function testRevalidationWithEtagReturnsNotModified(): void
    {
        ProjectFactory::new()->published()->create(['slug' => 'demo']);

        $this->client->request('GET', '/api/projects/demo');
        self::assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $etag = $response->getEtag();
        self::assertNotNull($etag);
        self::assertSame('max-age=60, public', $response->headers->get('Cache-Control'));

        $this->client->request('GET', '/api/projects/demo', server: ['HTTP_IF_NONE_MATCH' => $etag]);

        self::assertResponseStatusCodeSame(304);
        self::assertSame('', $this->client->getResponse()->getContent());
    }

    public function testCyrillicIsSentAsUtf8NotEscaped(): void
    {
        ProjectFactory::new()->published()->create(['slug' => 'demo', 'title' => 'Портфолио']);

        $this->client->request('GET', '/api/projects/demo');

        self::assertStringContainsString('"title":"Портфолио"', (string) $this->client->getResponse()->getContent());
    }
}
