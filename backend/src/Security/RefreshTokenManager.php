<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Issues, rotates and revokes refresh tokens. The token travels only in an httpOnly cookie
 * scoped to /api/auth, so page scripts can't read it and other endpoints never receive it.
 */
final readonly class RefreshTokenManager
{
    public const string COOKIE_NAME = 'refresh_token';
    private const string COOKIE_PATH = '/api/auth';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RefreshTokenRepository $tokens,
        #[Autowire(param: 'app.refresh_token_ttl')]
        private int $ttl,
    ) {
    }

    public function issue(User $user): Cookie
    {
        $now = new DateTimeImmutable();
        $this->tokens->deleteExpiredForUser($user, $now);

        $token = bin2hex(random_bytes(32));
        $expiresAt = $now->modify(\sprintf('+%d seconds', $this->ttl));

        $this->entityManager->persist(new RefreshToken($user, self::hash($token), $expiresAt));
        $this->entityManager->flush();

        return Cookie::create(self::COOKIE_NAME)
            ->withValue($token)
            ->withExpires($expiresAt)
            ->withPath(self::COOKIE_PATH)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    /**
     * Exchanges a valid token for a new one; the presented token stops working.
     * Returns null for an unknown, expired or already used token.
     */
    public function rotate(string $token): ?RotatedRefreshToken
    {
        $stored = $this->tokens->findValid(self::hash($token), new DateTimeImmutable());
        if (null === $stored) {
            return null;
        }

        $user = $stored->getUser();
        $this->entityManager->remove($stored);

        // issue() flushes the removal together with the new token
        return new RotatedRefreshToken($user, $this->issue($user));
    }

    public function revoke(string $token): void
    {
        $this->tokens->deleteByHash(self::hash($token));
    }

    public function clearCookie(Response $response): void
    {
        $response->headers->clearCookie(self::COOKIE_NAME, self::COOKIE_PATH, null, true, true, Cookie::SAMESITE_STRICT);
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
