<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiExceptionListener;
use App\Validation\InvalidRequest;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

final class ApiExceptionListenerTest extends TestCase
{
    public function testLeavesErrorsOutsideTheApiToSymfony(): void
    {
        $event = $this->handle(new NotFoundHttpException('No route'), '/admin');

        self::assertNull($event->getResponse());
    }

    public function testRendersHttpErrorsAsProblemJsonWithTheirHeaders(): void
    {
        $response = $this->handle(new TooManyRequestsHttpException(30, 'Slow down.'))->getResponse();

        self::assertNotNull($response);
        self::assertSame(429, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertSame('30', $response->headers->get('Retry-After'));
        self::assertSame(
            ['type' => 'about:blank', 'title' => 'Too Many Requests', 'status' => 429, 'detail' => 'Slow down.'],
            self::decode($response),
        );
    }

    public function testHidesServerErrorDetailsOutsideDebugMode(): void
    {
        $response = $this->handle(new LogicException('SQLSTATE[08006] password for user "app"'))->getResponse();

        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame(['type' => 'about:blank', 'title' => 'Internal Server Error', 'status' => 500], self::decode($response));
    }

    public function testShowsServerErrorDetailsInDebugMode(): void
    {
        $response = $this->handle(new LogicException('Broken invariant'), debug: true)->getResponse();

        self::assertNotNull($response);
        self::assertSame('Broken invariant', self::decode($response)['detail'] ?? null);
    }

    public function testEmptyMessageGivesNoDetail(): void
    {
        $response = $this->handle(new NotFoundHttpException())->getResponse();

        self::assertNotNull($response);
        self::assertArrayNotHasKey('detail', self::decode($response));
    }

    public function testListsViolationsFoundAnywhereInTheExceptionChain(): void
    {
        $response = $this->handle(InvalidRequest::at('slug', 'This slug is already used.', 'portfolio'))->getResponse();

        self::assertNotNull($response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame([
            'type' => 'about:blank',
            'title' => 'Unprocessable Content',
            'status' => 422,
            'detail' => 'The request contains invalid parameters.',
            'violations' => [['propertyPath' => 'slug', 'message' => 'This slug is already used.']],
        ], self::decode($response));
    }

    private function handle(Throwable $exception, string $path = '/api/projects', bool $debug = false): ExceptionEvent
    {
        $event = new ExceptionEvent($this->createStub(HttpKernelInterface::class), Request::create($path), HttpKernelInterface::MAIN_REQUEST, $exception);
        new ApiExceptionListener($debug)($event);

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
