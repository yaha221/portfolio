<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\Project;
use App\Factory\ProjectFactory;
use App\Factory\TechnologyFactory;
use App\Message\ProjectPublished;
use App\Tests\Functional\Api\ApiTestCase;
use League\Flysystem\FilesystemOperator;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[ResetDatabase]
final class ProjectAdminApiTest extends ApiTestCase
{
    use InteractsWithMessenger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
        TechnologyFactory::createOne(['name' => 'PHP', 'slug' => 'php']);
        TechnologyFactory::createOne(['name' => 'Vue', 'slug' => 'vue']);
        TechnologyFactory::createOne(['name' => 'Docker', 'slug' => 'docker']);
    }

    public function testCreatesProject(): void
    {
        ProjectFactory::createOne(['slug' => 'blog']);

        $data = $this->sendJson('POST', '/api/admin/projects', [
            'slug' => 'shop',
            'title' => 'Магазин',
            'description' => 'Интернет-магазин',
            'content' => '## Подробно',
            'repoUrl' => 'https://github.com/example/shop',
            'isPublished' => true,
            'sortOrder' => 3,
            'technologies' => ['vue', 'php'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('Location', '/api/admin/projects/'.$data['id']);
        self::assertSame(['php', 'vue'], array_column($data['technologies'], 'slug'));
        self::assertTrue($data['isPublished']);

        $project = ProjectFactory::repository()->findOneBy(['slug' => 'shop']);
        self::assertNotNull($project);
        self::assertSame('Магазин', $project->getTitle());
        self::assertSame(3, $project->getSortOrder());
    }

    public function testRejectsInvalidFields(): void
    {
        $this->sendJson('POST', '/api/admin/projects', [
            'slug' => 'Not A Slug',
            'title' => '',
            'description' => 'ok',
            'repoUrl' => 'not-a-url',
            'technologies' => ['php', 'php'],
        ]);

        self::assertSame(['slug', 'title', 'repoUrl', 'technologies'], $this->assertViolations());
        ProjectFactory::repository()->assert()->empty();
    }

    public function testRejectsTakenSlug(): void
    {
        ProjectFactory::createOne(['slug' => 'shop']);

        $this->sendJson('POST', '/api/admin/projects', ['slug' => 'shop', 'title' => 'T', 'description' => 'D']);

        self::assertSame(['slug'], $this->assertViolations());
    }

    public function testRejectsUnknownTechnology(): void
    {
        $this->sendJson('POST', '/api/admin/projects', ['slug' => 'shop', 'title' => 'T', 'description' => 'D', 'technologies' => ['php', 'cobol']]);

        self::assertSame(['technologies[1]'], $this->assertViolations());
    }

    public function testUpdateReplacesTechnologiesAndKeepsOwnSlug(): void
    {
        $project = $this->projectWith(['php', 'vue'], ['slug' => 'shop']);

        $data = $this->sendJson('PUT', '/api/admin/projects/'.$project->getId(), [
            'slug' => 'shop',
            'title' => 'Новое название',
            'description' => 'D',
            'technologies' => ['php', 'docker'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('Новое название', $data['title']);
        self::assertSame(['docker', 'php'], array_column($data['technologies'], 'slug'));
    }

    public function testUpdateSavesEveryFieldToTheDatabase(): void
    {
        $project = $this->projectWith(['php'], ['slug' => 'shop']);

        $this->sendJson('PUT', '/api/admin/projects/'.$project->getId(), [
            'slug' => 'web-shop',
            'title' => 'Интернет-магазин',
            'description' => 'Новое описание',
            'content' => '## Новый текст',
            'repoUrl' => 'https://github.com/example/shop',
            'demoUrl' => 'https://shop.example.com',
            'isPublished' => true,
            'sortOrder' => 4,
            'technologies' => ['vue'],
        ]);
        self::assertResponseIsSuccessful();

        $saved = $this->reload($project);
        self::assertSame(
            ['web-shop', 'Интернет-магазин', 'Новое описание', '## Новый текст', 'https://github.com/example/shop', 'https://shop.example.com', true, 4],
            [$saved->getSlug(), $saved->getTitle(), $saved->getDescription(), $saved->getContent(), $saved->getRepoUrl(), $saved->getDemoUrl(), $saved->isPublished(), $saved->getSortOrder()],
        );
        self::assertSame(['vue'], $saved->getTechnologies()->map(static fn ($technology) => $technology->getSlug())->getValues());
    }

    public function testUpdateRejectsTheSlugOfAnotherProject(): void
    {
        ProjectFactory::createOne(['slug' => 'blog']);
        $project = ProjectFactory::createOne(['slug' => 'shop']);

        $this->sendJson('PUT', '/api/admin/projects/'.$project->getId(), ['slug' => 'blog', 'title' => 'T', 'description' => 'D']);

        self::assertSame(['slug'], $this->assertViolations());
        self::assertSame('shop', $this->reload($project)->getSlug());
    }

    public function testPatchChangesOnlyGivenFields(): void
    {
        $project = ProjectFactory::createOne(['sortOrder' => 5, 'title' => 'Черновик']);
        $uri = '/api/admin/projects/'.$project->getId();

        $data = $this->sendJson('PATCH', $uri, ['isPublished' => true]);

        self::assertResponseIsSuccessful();
        self::assertTrue($data['isPublished']);
        self::assertSame(5, $data['sortOrder']);
        self::assertSame('Черновик', $data['title']);

        $data = $this->sendJson('PATCH', $uri, ['sortOrder' => 7]);

        self::assertSame([7, true], [$data['sortOrder'], $data['isPublished']]);
        self::assertSame(7, $this->reload($project)->getSortOrder());
    }

    public function testPublishingNotifiesOnlyOnTheDraftToPublishedTransition(): void
    {
        $project = ProjectFactory::createOne(['slug' => 'shop', 'title' => 'Магазин']);
        $uri = '/api/admin/projects/'.$project->getId();

        $this->sendJson('PATCH', $uri, ['isPublished' => true]);
        $this->sendJson('PATCH', $uri, ['sortOrder' => 7]);
        $this->sendJson('PATCH', $uri, ['isPublished' => true]);

        $queued = $this->transport('async')->queue()->messages(ProjectPublished::class);
        self::assertCount(1, $queued);
        self::assertSame($project->getId(), $queued[0]->projectId);

        $this->transport('async')->process();

        self::assertNotificationCount(1);
        $notification = self::getNotifierMessage();
        self::assertNotNull($notification);
        self::assertNotificationSubjectContains($notification, 'http://localhost:5173/projects/shop');
    }

    public function testCreatingPublishedProjectNotifiesButDraftDoesNot(): void
    {
        $this->sendJson('POST', '/api/admin/projects', ['slug' => 'live', 'title' => 'T', 'description' => 'D', 'isPublished' => true]);
        $this->sendJson('POST', '/api/admin/projects', ['slug' => 'draft', 'title' => 'T', 'description' => 'D']);

        $this->transport('async')->queue()->assertContains(ProjectPublished::class, 1);
    }

    public function testListIncludesDrafts(): void
    {
        ProjectFactory::new()->published()->create(['slug' => 'live', 'sortOrder' => 1]);
        ProjectFactory::createOne(['slug' => 'draft', 'sortOrder' => 2]);

        $data = $this->getJson('/api/admin/projects');

        self::assertSame(['live', 'draft'], array_column($data['items'], 'slug'));
        self::assertSame([true, false], array_column($data['items'], 'isPublished'));
    }

    public function testShowsDraftForEditing(): void
    {
        // The public API hides drafts; the admin needs them to edit before publishing
        $draft = ProjectFactory::createOne(['slug' => 'draft', 'title' => 'Черновик', 'content' => '## Текст']);

        $data = $this->getJson('/api/admin/projects/'.$draft->getId());

        self::assertSame($draft->getId(), $data['id']);
        self::assertSame(['draft', 'Черновик', '## Текст', false], [$data['slug'], $data['title'], $data['content'], $data['isPublished']]);
    }

    public function testUnknownProjectIsNotFound(): void
    {
        $this->client->request('GET', '/api/admin/projects/999999');

        $this->assertProblem(404);
    }

    public function testDeleteRemovesProjectAndItsFiles(): void
    {
        // Same kernel, so the request sees the in-memory uploads storage filled below
        $this->client->disableReboot();
        $project = ProjectFactory::new()
            ->afterInstantiate(static function (Project $project): void {
                $project->addImage('projects/shot.png')->setThumbnails(['small' => 'projects/shot-small.webp']);
            })
            ->create(['coverImage' => 'projects/cover.png']);
        $storage = $this->uploads();
        foreach (['projects/cover.png', 'projects/shot.png', 'projects/shot-small.webp'] as $path) {
            $storage->write($path, 'image bytes');
        }
        $id = $project->getId();

        $this->client->request('DELETE', '/api/admin/projects/'.$id);

        self::assertResponseStatusCodeSame(204);
        self::assertNull(ProjectFactory::repository()->find($id));
        self::assertSame([], $storage->listContents('projects')->toArray());
    }

    /**
     * @param list<string>         $technologySlugs
     * @param array<string, mixed> $attributes
     */
    private function projectWith(array $technologySlugs, array $attributes = []): Project
    {
        $technologies = array_map(
            static fn (string $slug) => TechnologyFactory::repository()->findOneBy(['slug' => $slug]),
            $technologySlugs,
        );

        return ProjectFactory::createOne([...$attributes, 'technologies' => $technologies]);
    }

    /**
     * Fresh from the database: the response alone would not show a missing flush.
     */
    private function reload(Project $project): Project
    {
        self::getContainer()->get('doctrine')->getManager()->clear();
        $reloaded = ProjectFactory::repository()->find($project->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }

    private function uploads(): FilesystemOperator
    {
        $storage = self::getContainer()->get('uploads.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        return $storage;
    }
}
