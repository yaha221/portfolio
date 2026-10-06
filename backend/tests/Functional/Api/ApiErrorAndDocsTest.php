<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class ApiErrorAndDocsTest extends ApiTestCase
{
    public function testUnknownRouteIsProblemJson(): void
    {
        $this->client->request('GET', '/api/unknown');

        self::assertSame('Not Found', $this->assertProblem(404)['title']);
    }

    public function testWrongMethodIsProblemJsonWithAllowHeader(): void
    {
        $this->client->request('POST', '/api/skills');

        $this->assertProblem(405);
        self::assertResponseHeaderSame('Allow', 'GET');
    }

    public function testOpenApiDocumentListsPublicEndpoints(): void
    {
        $this->client->request('GET', '/api/doc.json');
        self::assertResponseIsSuccessful();

        $doc = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($doc);
        self::assertSame(
            ['/api/experience', '/api/projects', '/api/projects/{slug}', '/api/skills', '/api/technologies'],
            array_keys($doc['paths']),
        );
    }
}
