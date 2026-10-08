<?php

declare(strict_types=1);

namespace App\MessageHandler\Contact;

use App\Message\ContactMessageReceived;
use App\Repository\ContactMessageRepository;
use App\Service\Notification\AdminChat;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class NotifyTelegramAboutContactMessage
{
    // Telegram limits a message to 4096 characters; the full text is in the email and the admin
    private const int PREVIEW_LENGTH = 1000;

    public function __construct(
        private ContactMessageRepository $messages,
        private AdminChat $chat,
    ) {
    }

    public function __invoke(ContactMessageReceived $event): void
    {
        $message = $this->messages->find($event->contactMessageId);
        if (null === $message) {
            return;
        }

        $text = $message->getMessage();
        if (mb_strlen($text) > self::PREVIEW_LENGTH) {
            $text = mb_substr($text, 0, self::PREVIEW_LENGTH).'…';
        }

        $this->chat->send(\sprintf("Новое сообщение с сайта\n%s <%s>\n\n%s", $message->getName(), $message->getEmail(), $text));
    }
}
