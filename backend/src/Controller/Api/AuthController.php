<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Request\LoginRequest;
use App\Dto\Response\TokenResponse;
use App\OpenApi\ProblemResponse;
use App\Security\RefreshTokenManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_', format: 'json')]
#[OA\Tag(name: 'Auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenManager $refreshTokens,
        private readonly JWTTokenManagerInterface $jwtManager,
        #[Autowire(param: 'lexik_jwt_authentication.token_ttl')]
        private readonly int $tokenTtl,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        summary: 'Вход администратора',
        description: 'Возвращает access-токен на 15 минут и ставит httpOnly-cookie refresh_token на 30 дней (path /api/auth). Не больше 5 неудачных попыток за 15 минут.',
    )]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: LoginRequest::class)))]
    #[OA\Response(response: 200, description: 'Вход выполнен', content: new OA\JsonContent(ref: new Model(type: TokenResponse::class)))]
    #[ProblemResponse(401, 'Неверный email или пароль')]
    #[ProblemResponse(429, 'Слишком много неудачных попыток')]
    public function login(): never
    {
        // json_login (security.yaml) answers valid requests before the controller is reached
        throw new BadRequestHttpException('Send {"email": "...", "password": "..."} as JSON.');
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    #[OA\Post(
        summary: 'Новый access-токен по refresh-cookie',
        description: 'Refresh-токен одноразовый: в ответе приходит новая cookie, старая перестаёт работать.',
    )]
    #[OA\Response(response: 200, description: 'Новый токен', content: new OA\JsonContent(ref: new Model(type: TokenResponse::class)))]
    #[ProblemResponse(401, 'Cookie нет, она истекла или уже использована')]
    public function refresh(Request $request): JsonResponse
    {
        $token = $request->cookies->getString(RefreshTokenManager::COOKIE_NAME);
        $rotated = '' === $token ? null : $this->refreshTokens->rotate($token);
        if (null === $rotated) {
            throw new UnauthorizedHttpException('Bearer', 'Refresh token is missing, expired or already used.');
        }

        $response = $this->json(new TokenResponse($this->jwtManager->create($rotated->user), $this->tokenTtl));
        $response->headers->setCookie($rotated->cookie);

        return $response;
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(summary: 'Выход', description: 'Отзывает refresh-токен из cookie. Выданный access-токен доживает свои 15 минут.')]
    #[OA\Response(response: 204, description: 'Сессия завершена')]
    public function logout(Request $request): Response
    {
        $token = $request->cookies->getString(RefreshTokenManager::COOKIE_NAME);
        if ('' !== $token) {
            $this->refreshTokens->revoke($token);
        }

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $this->refreshTokens->clearCookie($response);

        return $response;
    }
}
