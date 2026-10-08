<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Enum\ContactMessageStatus;
use App\Factory\ContactMessageFactory;
use App\Message\ContactMessageReceived;
use Symfony\Component\Mime\Email;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[ResetDatabase]
final class ContactApiTest extends ApiTestCase
{
    use InteractsWithMessenger;

    private const array VALID = ['name' => 'Иван', 'email' => 'ivan@example.com', 'message' => 'Здравствуйте! Хочу обсудить проект.'];

    protected function setUp(): void
    {
        parent::setUp();
        self::getContainer()->get('cache.rate_limiter')->clear();
    }

    public function testSavesMessageAndQueuesNotifications(): void
    {
        $this->sendJson('POST', '/api/contact', self::VALID);

        self::assertResponseStatusCodeSame(202);
        $message = ContactMessageFactory::repository()->findOneBy(['email' => 'ivan@example.com']);
        self::assertNotNull($message);
        self::assertSame(ContactMessageStatus::New, $message->getStatus());

        $queued = $this->transport('async')->queue()->messages(ContactMessageReceived::class);
        self::assertCount(1, $queued);
        self::assertSame($message->getId(), $queued[0]->contactMessageId);
        self::assertEmailCount(0);
    }

    public function testWorkerEmailsAdminAndVisitorAndNotifiesTelegram(): void
    {
        $this->sendJson('POST', '/api/contact', self::VALID);

        $this->transport('async')->process();

        self::assertEmailCount(2);
        $admin = self::getMailerMessage(0);
        self::assertInstanceOf(Email::class, $admin);
        self::assertEmailAddressContains($admin, 'To', 'admin@portfolio.local');
        self::assertEmailAddressContains($admin, 'Reply-To', 'ivan@example.com');
        self::assertEmailTextBodyContains($admin, self::VALID['message']);

        $autoReply = self::getMailerMessage(1);
        self::assertInstanceOf(Email::class, $autoReply);
        self::assertEmailAddressContains($autoReply, 'To', 'ivan@example.com');
        // The visitor's text is never echoed back: the form must not relay arbitrary content
        self::assertEmailTextBodyNotContains($autoReply, self::VALID['message']);

        self::assertNotificationCount(1);
        self::assertSame(ContactMessageStatus::Sent, ContactMessageFactory::repository()->findOneBy([])?->getStatus());
    }

    public function testRejectsInvalidMessage(): void
    {
        $this->sendJson('POST', '/api/contact', ['name' => ' ', 'email' => 'not-an-email', 'message' => 'short']);

        self::assertSame(['name', 'email', 'message'], $this->assertViolations());
        $this->transport('async')->queue()->assertEmpty();
    }

    public function testHoneypotIsAcceptedButIgnored(): void
    {
        $this->sendJson('POST', '/api/contact', [...self::VALID, 'website' => 'http://spam.example']);

        self::assertResponseStatusCodeSame(202);
        ContactMessageFactory::repository()->assert()->empty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function testLimitsMessagesPerAddress(): void
    {
        for ($i = 1; $i <= 3; ++$i) {
            $this->sendJson('POST', '/api/contact', self::VALID);
            self::assertResponseStatusCodeSame(202);
        }

        $this->sendJson('POST', '/api/contact', self::VALID);

        $this->assertProblem(429);
        self::assertResponseHasHeader('Retry-After');
        ContactMessageFactory::repository()->assert()->count(3);
    }
}
