<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\RefreshToken;
use App\Factory\UserFactory;
use App\Repository\RefreshTokenRepository;
use App\Security\RefreshTokenManager;
use DateTimeImmutable;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class AuthApiTest extends ApiTestCase
{
    private const string EMAIL = 'admin@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        // Login throttling counters live in this pool and would otherwise carry over between tests and runs
        self::getContainer()->get('cache.rate_limiter')->clear();
        UserFactory::new()->admin()->create(['email' => self::EMAIL]);
    }

    public function testLoginReturnsTokenAndSecureRefreshCookie(): void
    {
        $data = $this->login();

        self::assertSame(900, $data['expiresIn']);
        self::assertCount(3, explode('.', $data['token']), 'JWT has header.payload.signature');

        $cookies = $this->client->getResponse()->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame(RefreshTokenManager::COOKIE_NAME, $cookies[0]->getName());
        self::assertSame('/api/auth', $cookies[0]->getPath());
        self::assertTrue($cookies[0]->isHttpOnly());
        self::assertTrue($cookies[0]->isSecure());
        self::assertSame('strict', $cookies[0]->getSameSite());
        // 256 random bits, hex-encoded
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $cookies[0]->getValue());
    }

    public function testLoginRemovesOnlyTheUsersExpiredTokens(): void
    {
        $admin = UserFactory::repository()->findOneBy(['email' => self::EMAIL]);
        $other = UserFactory::createOne();
        self::assertNotNull($admin);
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist(new RefreshToken($admin, str_repeat('a', 64), new DateTimeImmutable('-1 day')));
        $entityManager->persist(new RefreshToken($admin, str_repeat('b', 64), new DateTimeImmutable('+1 day')));
        $entityManager->persist(new RefreshToken($other, str_repeat('c', 64), new DateTimeImmutable('-1 day')));
        $entityManager->flush();

        $this->login();

        $hashes = array_map(
            static fn (RefreshToken $token) => $token->getTokenHash(),
            self::getContainer()->get(RefreshTokenRepository::class)->findAll(),
        );
        self::assertNotContains(str_repeat('a', 64), $hashes);
        self::assertContains(str_repeat('b', 64), $hashes, 'A valid token from another device keeps working');
        self::assertContains(str_repeat('c', 64), $hashes, 'Other users are cleaned up on their own login');
        self::assertCount(3, $hashes);
    }

    public function testRefreshTokenIsStoredOnlyAsHash(): void
    {
        $this->login();
        $token = $this->refreshCookieValue();

        $tokens = self::getContainer()->get(RefreshTokenRepository::class);
        self::assertNull($tokens->findOneBy(['tokenHash' => $token]));
        self::assertNotNull($tokens->findOneBy(['tokenHash' => hash('sha256', $token)]));
    }

    public function testLoginNeedsJson(): void
    {
        $this->client->request('POST', '/api/auth/login', ['email' => self::EMAIL, 'password' => 'password']);

        self::assertSame('Invalid JSON.', $this->assertProblem(400)['detail']);
        self::assertResponseNotHasCookie(RefreshTokenManager::COOKIE_NAME, '/api/auth');
    }

    public function testWrongPasswordIsUnauthorized(): void
    {
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => self::EMAIL, 'password' => 'wrong']);

        self::assertSame('Invalid credentials.', $this->assertProblem(401)['detail']);
        self::assertResponseNotHasCookie(RefreshTokenManager::COOKIE_NAME, '/api/auth');
    }

    public function testRefreshRotatesTheToken(): void
    {
        $this->login();
        $first = $this->refreshCookieValue();

        $this->client->request('POST', '/api/auth/refresh');
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->decodeResponse()['token']);
        self::assertNotSame($first, $this->refreshCookieValue());

        // The first token was single-use
        $this->setRefreshCookie($first);
        $this->client->request('POST', '/api/auth/refresh');
        $this->assertProblem(401);
    }

    public function testRefreshWithoutCookieIsUnauthorized(): void
    {
        $this->client->request('POST', '/api/auth/refresh');

        $this->assertProblem(401);
    }

    public function testLogoutRevokesTheRefreshToken(): void
    {
        $this->login();
        $token = $this->refreshCookieValue();

        $this->client->request('POST', '/api/auth/logout');
        self::assertResponseStatusCodeSame(204);
        self::assertSame(0, self::getContainer()->get(RefreshTokenRepository::class)->count([]));

        // The browser drops the cookie only if path and flags match the ones it was set with
        $cleared = $this->client->getResponse()->headers->getCookies();
        self::assertCount(1, $cleared);
        self::assertSame(RefreshTokenManager::COOKIE_NAME, $cleared[0]->getName());
        self::assertTrue($cleared[0]->isCleared());
        self::assertSame(['/api/auth', true, true, 'strict'], [$cleared[0]->getPath(), $cleared[0]->isSecure(), $cleared[0]->isHttpOnly(), $cleared[0]->getSameSite()]);

        $this->setRefreshCookie($token);
        $this->client->request('POST', '/api/auth/refresh');
        $this->assertProblem(401);
    }

    public function testRepeatedFailedLoginsAreThrottled(): void
    {
        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $this->client->jsonRequest('POST', '/api/auth/login', ['email' => self::EMAIL, 'password' => 'wrong']);
            $this->assertProblem(401);
        }

        $this->login(expectSuccess: false);
        $this->assertProblem(429);
    }

    public function testAccessTokenFromLoginOpensAdminApi(): void
    {
        $token = $this->login()['token'];

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
        $this->client->request('GET', '/api/admin/skills');

        self::assertResponseIsSuccessful();
    }

    /**
     * @return array<string, mixed>
     */
    private function login(bool $expectSuccess = true): array
    {
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => self::EMAIL, 'password' => UserFactory::DEFAULT_PASSWORD]);
        if (!$expectSuccess) {
            return [];
        }

        self::assertResponseIsSuccessful();

        return $this->decodeResponse();
    }

    private function refreshCookieValue(): string
    {
        $cookie = $this->client->getCookieJar()->get(RefreshTokenManager::COOKIE_NAME, '/api/auth', 'localhost');
        self::assertNotNull($cookie);

        return $cookie->getValue();
    }

    private function setRefreshCookie(string $value): void
    {
        $this->client->getCookieJar()->set(
            new Cookie(RefreshTokenManager::COOKIE_NAME, $value, null, '/api/auth', 'localhost', true, true, false, 'strict'),
        );
    }
}
