<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\ContactMessage;
use App\Enum\ContactMessageStatus;
use App\Factory\ContactMessageFactory;
use App\Message\ContactMessageReceived;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class MarkUndeliveredContactMessageTest extends KernelTestCase
{
    public function testMarksMessageFailedAfterTheLastRetry(): void
    {
        $message = ContactMessageFactory::createOne();

        $this->dispatchFailure($message, willRetry: false);

        self::assertSame(ContactMessageStatus::Failed, $this->reload($message)->getStatus());
    }

    public function testKeepsStatusWhileRetriesRemain(): void
    {
        $message = ContactMessageFactory::createOne();

        $this->dispatchFailure($message, willRetry: true);

        self::assertSame(ContactMessageStatus::New, $this->reload($message)->getStatus());
    }

    public function testSentMessageStaysSentWhenOnlyAnotherHandlerFailed(): void
    {
        $message = ContactMessageFactory::new()->afterInstantiate(static fn (ContactMessage $m) => $m->markSent())->create();

        $this->dispatchFailure($message, willRetry: false);

        self::assertSame(ContactMessageStatus::Sent, $this->reload($message)->getStatus());
    }

    /**
     * Messenger's own retry listener (higher priority) decides willRetry from the RedeliveryStamp,
     * exactly as in the worker: 3 redeliveries = max_retries reached.
     */
    private function dispatchFailure(ContactMessage $message, bool $willRetry): void
    {
        $event = new WorkerMessageFailedEvent(
            new Envelope(new ContactMessageReceived((int) $message->getId()), [new RedeliveryStamp($willRetry ? 0 : 3)]),
            'async',
            new RuntimeException('SMTP is down'),
        );

        self::getContainer()->get(EventDispatcherInterface::class)->dispatch($event);
        self::assertSame($willRetry, $event->willRetry());
    }

    private function reload(ContactMessage $message): ContactMessage
    {
        $reloaded = ContactMessageFactory::repository()->find($message->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }
}
