<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\Project;
use App\Factory\ProjectFactory;
use App\Message\ProjectImageUploaded;
use App\Tests\Functional\Api\ApiTestCase;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[ResetDatabase]
final class ProjectMediaAdminApiTest extends ApiTestCase
{
    use InteractsWithMessenger;

    // 1×1 PNG
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    private const string UPLOADS_URL = 'http://localhost:8080/uploads/';

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        // One kernel for the whole test: the in-memory uploads storage must survive between requests
        $this->client->disableReboot();
        $this->loginAsAdmin();
        $this->project = ProjectFactory::createOne();
    }

    public function testUploadsImagesToTheEndOfTheGallery(): void
    {
        $first = $this->upload('/images', $this->png());
        self::assertResponseStatusCodeSame(201);
        $second = $this->upload('/images', $this->png());

        self::assertSame([0, 1], [$first['position'], $second['position']]);
        self::assertSame([], $first['thumbnails']);
        self::assertMatchesRegularExpression('#^'.self::UPLOADS_URL.'projects/[0-9a-f-]{36}\.png$#', $first['url']);
        self::assertTrue($this->uploads()->fileExists($this->pathOf($first['url'])));
        self::assertCount(2, $this->reloadProject()->getImages());
    }

    public function testWorkerAddsThumbnailsToUploadedImage(): void
    {
        $this->upload('/images', $this->png());
        $this->transport('async')->queue()->assertContains(ProjectImageUploaded::class, 1);

        $this->transport('async')->process();

        $image = $this->reloadProject()->getImages()->first();
        self::assertNotFalse($image);
        // JSONB keeps its own key order
        self::assertEqualsCanonicalizing(['small', 'large'], array_keys($image->getThumbnails()));
        foreach ($image->getThumbnails() as $path) {
            self::assertTrue($this->uploads()->fileExists($path));
        }
    }

    public function testRejectsFileThatIsNotAnImage(): void
    {
        $text = $this->tempFile('hello', 'notes.txt', 'text/plain');

        $this->upload('/images', $text);

        self::assertSame(['file'], $this->assertViolations());
        self::assertSame([], $this->uploads()->listContents('projects')->toArray());
    }

    public function testRejectsRequestWithoutFile(): void
    {
        $this->client->request('POST', $this->base().'/images');

        self::assertSame(['file'], $this->assertViolations());
    }

    public function testDeletesImageAndItsFile(): void
    {
        $image = $this->upload('/images', $this->png());

        $this->client->request('DELETE', $this->base().'/images/'.$image['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertCount(0, $this->reloadProject()->getImages());
        self::assertFalse($this->uploads()->fileExists($this->pathOf($image['url'])));
    }

    public function testImageOfAnotherProjectIsNotFound(): void
    {
        $image = $this->upload('/images', $this->png());
        $other = ProjectFactory::createOne();

        $this->client->request('DELETE', '/api/admin/projects/'.$other->getId().'/images/'.$image['id']);

        $this->assertProblem(404);
    }

    public function testReplacingCoverDeletesThePreviousFile(): void
    {
        $firstCover = $this->upload('/cover', $this->png())['coverImageUrl'];
        $secondCover = $this->upload('/cover', $this->png())['coverImageUrl'];

        self::assertResponseIsSuccessful();
        self::assertNotSame($firstCover, $secondCover);
        self::assertFalse($this->uploads()->fileExists($this->pathOf($firstCover)));
        self::assertTrue($this->uploads()->fileExists($this->pathOf($secondCover)));

        $this->client->request('DELETE', $this->base().'/cover');
        self::assertResponseStatusCodeSame(204);
        self::assertNull($this->reloadProject()->getCoverImage());
        self::assertFalse($this->uploads()->fileExists($this->pathOf($secondCover)));
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $endpoint, UploadedFile $file): array
    {
        $this->client->request('POST', $this->base().$endpoint, files: ['file' => $file]);

        return $this->decodeResponse();
    }

    // The browser resets Doctrine between requests, so the instance from setUp() is stale
    private function reloadProject(): Project
    {
        $project = ProjectFactory::repository()->find($this->project->getId());
        self::assertNotNull($project);

        return $project;
    }

    private function base(): string
    {
        return '/api/admin/projects/'.$this->project->getId();
    }

    private function png(): UploadedFile
    {
        return $this->tempFile((string) base64_decode(self::PNG, true), 'shot.png', 'image/png');
    }

    private function tempFile(string $contents, string $clientName, string $clientMimeType): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        self::assertIsString($path);
        file_put_contents($path, $contents);

        return new UploadedFile($path, $clientName, $clientMimeType, null, true);
    }

    private function pathOf(string $url): string
    {
        self::assertStringStartsWith(self::UPLOADS_URL, $url);

        return substr($url, \strlen(self::UPLOADS_URL));
    }

    private function uploads(): FilesystemOperator
    {
        $storage = self::getContainer()->get('uploads.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        return $storage;
    }
}
