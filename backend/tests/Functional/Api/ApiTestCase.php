<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
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
     * @return array<string, mixed>
     */
    private function decodeResponse(): array
    {
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
