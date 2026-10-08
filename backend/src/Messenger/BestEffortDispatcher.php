<?php

declare(strict_types=1);

namespace App\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Queues follow-up work after the main change is already saved. If RabbitMQ is down,
 * the request still succeeds and the error is logged: a contact message stays in the
 * admin list, an image just has no thumbnails yet.
 */
final readonly class BestEffortDispatcher
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatch(object $message): void
    {
        try {
            $this->bus->dispatch($message);
        } catch (TransportException $exception) {
            $this->logger->error('Could not queue {message}: {error}', [
                'message' => $message::class,
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }
}
