<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler\Contact;

use App\Entity\ContactMessage;
use App\Message\ContactMessageReceived;
use App\MessageHandler\Contact\SendContactAutoReply;
use App\Repository\ContactMessageRepository;
use App\Tests\Support\SpyMailer;
use PHPUnit\Framework\TestCase;

final class SendContactAutoReplyTest extends TestCase
{
    public function testRepliesToTheVisitorWithoutEchoingTheirText(): void
    {
        $mailer = new SpyMailer();

        $this->handler(new ContactMessage('Иван', 'ivan@example.com', 'Ссылка: http://spam.example'), $mailer)(new ContactMessageReceived(7));

        $email = $mailer->onlyEmail();
        self::assertSame('ivan@example.com', $email->getTo()[0]->getAddress());
        self::assertSame('Иван', $email->getTo()[0]->getName());
        self::assertSame('Сообщение получено', $email->getSubject());
        // A form that mails arbitrary text to any address would be a spam relay
        self::assertStringNotContainsString('spam.example', (string) $email->getTextBody());
    }

    public function testDeletedMessageIsSkipped(): void
    {
        $mailer = new SpyMailer();

        $this->handler(null, $mailer)(new ContactMessageReceived(7));

        self::assertSame([], $mailer->sent);
    }

    private function handler(?ContactMessage $message, SpyMailer $mailer): SendContactAutoReply
    {
        $messages = $this->createMock(ContactMessageRepository::class);
        $messages->expects(self::once())->method('find')->with(7)->willReturn($message);

        return new SendContactAutoReply($messages, $mailer);
    }
}
