<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Dto\Response\ProblemDetails;
use App\Entity\User;
use App\Security\RefreshTokenManager;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * Shapes LexikJWT responses: adds the refresh cookie on login and turns
 * authentication failures into problem+json like every other API error.
 */
final readonly class JwtAuthenticationListener
{
    public function __construct(
        private RefreshTokenManager $refreshTokens,
        #[Autowire(param: 'lexik_jwt_authentication.token_ttl')]
        private int $tokenTtl,
    ) {
    }

    #[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
    public function onLoginSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $event->setData([...$event->getData(), 'expiresIn' => $this->tokenTtl]);
        $event->getResponse()->headers->setCookie($this->refreshTokens->issue($user));
    }

    // Wrong credentials, missing/invalid/expired access token
    #[AsEventListener(event: Events::AUTHENTICATION_FAILURE)]
    #[AsEventListener(event: Events::JWT_NOT_FOUND)]
    #[AsEventListener(event: Events::JWT_INVALID)]
    #[AsEventListener(event: Events::JWT_EXPIRED)]
    public function onFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        $status = $exception instanceof TooManyLoginAttemptsAuthenticationException
            ? Response::HTTP_TOO_MANY_REQUESTS
            : Response::HTTP_UNAUTHORIZED;
        $detail = strtr($exception->getMessageKey(), $exception->getMessageData());

        $problem = new ProblemDetails(Response::$statusTexts[$status], $status, $detail);
        $event->setResponse($problem->toResponse(['WWW-Authenticate' => 'Bearer']));
    }
}
