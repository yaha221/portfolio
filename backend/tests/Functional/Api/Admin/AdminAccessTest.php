<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Factory\UserFactory;
use App\Tests\Functional\Api\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class AdminAccessTest extends ApiTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function adminEndpoints(): iterable
    {
        yield 'list projects' => ['GET', '/api/admin/projects'];
        yield 'create project' => ['POST', '/api/admin/projects'];
        yield 'delete project' => ['DELETE', '/api/admin/projects/1'];
        yield 'upload image' => ['POST', '/api/admin/projects/1/images'];
        yield 'technologies' => ['GET', '/api/admin/technologies'];
        yield 'skills' => ['GET', '/api/admin/skills'];
        yield 'experience' => ['GET', '/api/admin/experience'];
        yield 'messages' => ['GET', '/api/admin/messages'];
    }

    #[DataProvider('adminEndpoints')]
    public function testRequiresAccessToken(string $method, string $uri): void
    {
        $this->client->jsonRequest($method, $uri);

        self::assertSame('JWT Token not found', $this->assertProblem(401)['detail']);
        self::assertResponseHeaderSame('WWW-Authenticate', 'Bearer');
    }

    #[DataProvider('adminEndpoints')]
    public function testRequiresAdminRole(string $method, string $uri): void
    {
        $this->loginAs(UserFactory::createOne());

        $this->client->jsonRequest($method, $uri);

        $this->assertProblem(403);
    }

    public function testRejectsMalformedToken(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer not.a.jwt');

        $this->client->request('GET', '/api/admin/skills');

        self::assertSame('Invalid JWT Token', $this->assertProblem(401)['detail']);
    }
}
