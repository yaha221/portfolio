<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler\Contact;

use App\Entity\ContactMessage;
use App\Message\ContactMessageReceived;
use App\MessageHandler\Contact\NotifyTelegramAboutContactMessage;
use App\Repository\ContactMessageRepository;
use App\Service\Notification\AdminChat;
use App\Tests\Support\SpyChatter;
use PHPUnit\Framework\TestCase;

final class NotifyTelegramAboutContactMessageTest extends TestCase
{
    public function testSendsSenderAndTextToTelegram(): void
    {
        $chatter = new SpyChatter();

        $this->handler(new ContactMessage('Иван', 'ivan@example.com', 'Хочу обсудить проект.'), $chatter)(new ContactMessageReceived(3));

        $message = $chatter->onlyMessage();
        self::assertSame("Новое сообщение с сайта\nИван <ivan@example.com>\n\nХочу обсудить проект.", $message->getSubject());
        self::assertSame('telegram', $message->getTransport());
    }

    public function testShortensLongTextToFitATelegramMessage(): void
    {
        $chatter = new SpyChatter();
        // Multibyte text: the cut must count characters, not bytes
        $text = str_repeat('я', 1000).'хвост';

        $this->handler(new ContactMessage('Иван', 'ivan@example.com', $text), $chatter)(new ContactMessageReceived(3));

        $subject = $chatter->onlyMessage()->getSubject();
        self::assertStringEndsWith("\n\n".str_repeat('я', 1000).'…', $subject);
        self::assertTrue(mb_check_encoding($subject, 'UTF-8'));
    }

    public function testTextOfExactlyTheLimitIsNotShortened(): void
    {
        $chatter = new SpyChatter();
        $text = str_repeat('я', 1000);

        $this->handler(new ContactMessage('Иван', 'ivan@example.com', $text), $chatter)(new ContactMessageReceived(3));

        self::assertStringEndsWith("\n\n".$text, $chatter->onlyMessage()->getSubject());
    }

    public function testDeletedMessageIsSkipped(): void
    {
        $chatter = new SpyChatter();

        $this->handler(null, $chatter)(new ContactMessageReceived(3));

        self::assertSame([], $chatter->sent);
    }

    private function handler(?ContactMessage $message, SpyChatter $chatter): NotifyTelegramAboutContactMessage
    {
        $messages = $this->createMock(ContactMessageRepository::class);
        $messages->expects(self::once())->method('find')->with(3)->willReturn($message);

        return new NotifyTelegramAboutContactMessage($messages, new AdminChat($chatter));
    }
}
