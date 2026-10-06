<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Dto\Response\ProblemDetails;
use App\Dto\Response\Violation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

/**
 * Renders every error under /api as application/problem+json (RFC 9457).
 *
 * Priority -64: after Symfony logs the exception (0), before its HTML/JSON error renderer (-128).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final readonly class ApiExceptionListener
{
    public function __construct(
        #[Autowire(param: 'kernel.debug')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
        $title = Response::$statusTexts[$status] ?? 'Error';

        $violations = $this->findViolations($exception);
        if (null !== $violations) {
            $problem = new ProblemDetails($title, $status, 'The request contains invalid parameters.', $violations);
        } else {
            // Messages of server errors may leak internals, so they are shown only in debug mode
            $detail = $status < 500 || $this->debug ? $exception->getMessage() : null;
            $problem = new ProblemDetails($title, $status, '' === $detail ? null : $detail);
        }

        $event->setResponse($problem->toResponse($headers));
    }

    /**
     * @return list<Violation>|null
     */
    private function findViolations(Throwable $exception): ?array
    {
        for ($current = $exception; null !== $current; $current = $current->getPrevious()) {
            if ($current instanceof ValidationFailedException) {
                $violations = [];
                /** @var ConstraintViolationInterface $violation */
                foreach ($current->getViolations() as $violation) {
                    $violations[] = new Violation($violation->getPropertyPath(), (string) $violation->getMessage());
                }

                return $violations;
            }
        }

        return null;
    }
}
