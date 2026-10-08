<?php

declare(strict_types=1);

namespace App\Tests\Unit\Messenger;

use App\Messenger\BestEffortDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

final class BestEffortDispatcherTest extends TestCase
{
    public function testLogsInsteadOfFailingWhenTheBrokerIsDown(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new TransportException('Connection refused'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        new BestEffortDispatcher($bus, $logger)->dispatch(new stdClass());
    }

    public function testDispatchesNormally(): void
    {
        $message = new stdClass();
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->with($message)->willReturn(new Envelope($message));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        new BestEffortDispatcher($bus, $logger)->dispatch($message);
    }
}
