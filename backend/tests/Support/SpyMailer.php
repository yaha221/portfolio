<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Assert;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Remembers sent emails instead of delivering them: for unit tests without the kernel.
 */
final class SpyMailer implements MailerInterface
{
    /** @var list<RawMessage> */
    public array $sent = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->sent[] = $message;
    }

    public function onlyEmail(): Email
    {
        Assert::assertCount(1, $this->sent);
        Assert::assertInstanceOf(Email::class, $this->sent[0]);

        return $this->sent[0];
    }
}
