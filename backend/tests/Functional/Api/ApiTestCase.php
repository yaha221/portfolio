<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        // HTTPS: the refresh cookie is Secure, so the test browser sends it back only over https
        $this->client = self::createClient(server: ['HTTPS' => 'on']);
    }

    /**
     * Signs further requests with an access token, skipping the login endpoint.
     */
    protected function loginAs(User $user): void
    {
        $token = self::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
    }

    protected function loginAsAdmin(): User
    {
        $admin = UserFactory::new()->admin()->create();
        $this->loginAs($admin);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJson(string $uri): array
    {
        $this->client->request('GET', $uri);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        return $this->decodeResponse();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed> empty for an empty body (204)
     */
    protected function sendJson(string $method, string $uri, array $data = []): array
    {
        $this->client->jsonRequest($method, $uri, $data);

        return '' === $this->client->getResponse()->getContent() ? [] : $this->decodeResponse();
    }

    /**
     * @return array<string, mixed>
     */
    protected function assertProblem(int $status): array
    {
        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');

        $problem = $this->decodeResponse();
        self::assertSame($status, $problem['status']);

        return $problem;
    }

    /**
     * @return list<string> property paths of the violations in a 400/422 problem
     */
    protected function assertViolations(int $status = 422): array
    {
        return array_column($this->assertProblem($status)['violations'], 'propertyPath');
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeResponse(): array
    {
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
