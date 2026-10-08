<?php

declare(strict_types=1);

namespace App\Message;

/**
 * A visitor sent the contact form; the message is already saved.
 * Handlers: email to the admin, auto-reply to the visitor, Telegram notification.
 */
final readonly class ContactMessageReceived
{
    public function __construct(
        public int $contactMessageId,
    ) {
    }
}
