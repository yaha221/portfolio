<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\ContactMessageStatus;
use App\Message\ContactMessageReceived;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * When the admin email still hasn't gone out after the last retry, flags the message as "failed"
 * so the admin list shows it. A failed Telegram notification alone doesn't count: the status is about the email.
 * `messenger:failed:retry` re-runs the handlers and the status becomes "sent".
 */
#[AsEventListener]
final readonly class MarkUndeliveredContactMessage
{
    public function __construct(
        private ContactMessageRepository $messages,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $queued = $event->getEnvelope()->getMessage();
        if ($event->willRetry() || !$queued instanceof ContactMessageReceived) {
            return;
        }

        $message = $this->messages->find($queued->contactMessageId);
        if (null === $message || ContactMessageStatus::New !== $message->getStatus()) {
            return;
        }

        $message->markFailed();
        $this->entityManager->flush();
    }
}
