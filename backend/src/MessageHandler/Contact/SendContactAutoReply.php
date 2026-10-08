<?php

declare(strict_types=1);

namespace App\MessageHandler\Contact;

use App\Message\ContactMessageReceived;
use App\Repository\ContactMessageRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Confirms receipt to the visitor. The text is fixed and never quotes the visitor's message:
 * otherwise the form could be used to send arbitrary text to any address.
 */
#[AsMessageHandler]
final readonly class SendContactAutoReply
{
    public function __construct(
        private ContactMessageRepository $messages,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(ContactMessageReceived $event): void
    {
        $message = $this->messages->find($event->contactMessageId);
        if (null === $message) {
            return;
        }

        $this->mailer->send(new Email()
            ->to(new Address($message->getEmail(), $message->getName()))
            ->subject('Сообщение получено')
            ->text("Здравствуйте!\n\nСпасибо, ваше сообщение получено. Отвечу в ближайшее время.\n\nЭто автоматическое письмо, отвечать на него не нужно.\n"));
    }
}
