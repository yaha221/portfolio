<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Assert;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;

/**
 * Remembers chat messages instead of sending them: for unit tests without the kernel.
 */
final class SpyChatter implements ChatterInterface
{
    /** @var list<MessageInterface> */
    public array $sent = [];

    public function send(MessageInterface $message): ?SentMessage
    {
        $this->sent[] = $message;

        return null;
    }

    public function supports(MessageInterface $message): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return 'spy://';
    }

    public function onlyMessage(): ChatMessage
    {
        Assert::assertCount(1, $this->sent);
        Assert::assertInstanceOf(ChatMessage::class, $this->sent[0]);

        return $this->sent[0];
    }
}
