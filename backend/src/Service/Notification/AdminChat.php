<?php

declare(strict_types=1);

namespace App\Service\Notification;

use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

/**
 * Short text notifications for the site owner in Telegram (TELEGRAM_DSN; null://null drops them).
 * Plain text on purpose: Telegram Markdown would need escaping of user-supplied content.
 */
final readonly class AdminChat
{
    public function __construct(
        private ChatterInterface $chatter,
    ) {
    }

    public function send(string $text): void
    {
        $this->chatter->send(new ChatMessage($text)->transport('telegram'));
    }
}
