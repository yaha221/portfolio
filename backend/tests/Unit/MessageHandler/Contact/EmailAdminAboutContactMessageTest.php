<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler\Contact;

use App\Entity\ContactMessage;
use App\Enum\ContactMessageStatus;
use App\Message\ContactMessageReceived;
use App\MessageHandler\Contact\EmailAdminAboutContactMessage;
use App\Repository\ContactMessageRepository;
use App\Tests\Support\SpyMailer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

final class EmailAdminAboutContactMessageTest extends TestCase
{
    private const int ID = 42;

    public function testEmailsTheAdminWithReplyToTheVisitorAndMarksTheMessageSent(): void
    {
        $message = new ContactMessage('Иван', 'ivan@example.com', 'Хочу обсудить проект.');
        $mailer = new SpyMailer();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $this->handler($message, $mailer, $entityManager)(new ContactMessageReceived(self::ID));

        $email = $mailer->onlyEmail();
        self::assertSame('admin@example.com', $email->getTo()[0]->getAddress());
        self::assertSame('ivan@example.com', $email->getReplyTo()[0]->getAddress());
        self::assertSame('Иван', $email->getReplyTo()[0]->getName());
        self::assertSame('Сообщение с сайта от Иван', $email->getSubject());
        self::assertStringContainsString('Хочу обсудить проект.', (string) $email->getTextBody());
        self::assertSame(ContactMessageStatus::Sent, $message->getStatus());
    }

    public function testRedeliveryAfterTheEmailWentOutSendsNothing(): void
    {
        $message = new ContactMessage('Иван', 'ivan@example.com', 'Хочу обсудить проект.');
        $message->markSent();
        $mailer = new SpyMailer();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $this->handler($message, $mailer, $entityManager)(new ContactMessageReceived(self::ID));

        self::assertSame([], $mailer->sent);
    }

    public function testDeletedMessageIsSkipped(): void
    {
        $mailer = new SpyMailer();

        $this->handler(null, $mailer, $this->createStub(EntityManagerInterface::class))(new ContactMessageReceived(self::ID));

        self::assertSame([], $mailer->sent);
    }

    public function testMessageStaysNewWhenSendingFails(): void
    {
        // The status must change only after a successful send, so the retry sends the email again
        $message = new ContactMessage('Иван', 'ivan@example.com', 'Хочу обсудить проект.');
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException(new TransportException('SMTP is down'));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        try {
            $this->handler($message, $mailer, $entityManager)(new ContactMessageReceived(self::ID));
            self::fail('The transport error must reach Messenger to schedule a retry.');
        } catch (TransportException) {
        }

        self::assertSame(ContactMessageStatus::New, $message->getStatus());
    }

    private function handler(?ContactMessage $message, MailerInterface $mailer, EntityManagerInterface $entityManager): EmailAdminAboutContactMessage
    {
        $messages = $this->createMock(ContactMessageRepository::class);
        $messages->expects(self::once())->method('find')->with(self::ID)->willReturn($message);

        return new EmailAdminAboutContactMessage($messages, $mailer, $entityManager, 'admin@example.com');
    }
}
