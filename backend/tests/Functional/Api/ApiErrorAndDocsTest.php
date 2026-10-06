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

    public function testOpenApiDocumentDescribesTheApi(): void
    {
        $doc = $this->getJson('/api/doc.json');

        $expected = [
            '/api/projects', '/api/projects/{slug}', '/api/technologies', '/api/skills', '/api/experience',
            '/api/auth/login', '/api/auth/refresh', '/api/auth/logout',
            '/api/admin/projects', '/api/admin/projects/{id}', '/api/admin/projects/{id}/images', '/api/admin/messages',
        ];
        self::assertSame([], array_diff($expected, array_keys($doc['paths'])));
        self::assertSame([['Bearer' => []]], $doc['paths']['/api/admin/projects']['get']['security']);
    }
}
