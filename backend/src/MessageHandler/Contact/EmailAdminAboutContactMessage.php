<?php

declare(strict_types=1);

namespace App\MessageHandler\Contact;

use App\Enum\ContactMessageStatus;
use App\Message\ContactMessageReceived;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends the message to the site owner and marks it as delivered.
 * Reply-To is the visitor, so answering the email goes straight to them.
 */
#[AsMessageHandler]
final readonly class EmailAdminAboutContactMessage
{
    public function __construct(
        private ContactMessageRepository $messages,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        #[Autowire(param: 'app.admin_email')]
        private string $adminEmail,
    ) {
    }

    public function __invoke(ContactMessageReceived $event): void
    {
        $message = $this->messages->find($event->contactMessageId);
        // Deleted meanwhile, or a redelivery after the email already went out
        if (null === $message || ContactMessageStatus::Sent === $message->getStatus()) {
            return;
        }

        $this->mailer->send(new Email()
            ->to($this->adminEmail)
            ->replyTo(new Address($message->getEmail(), $message->getName()))
            ->subject(\sprintf('Сообщение с сайта от %s', $message->getName()))
            ->text(\sprintf(
                "Имя: %s\nEmail: %s\nОтправлено: %s UTC\n\n%s\n",
                $message->getName(),
                $message->getEmail(),
                $message->getCreatedAt()->format('d.m.Y H:i'),
                $message->getMessage(),
            )));

        $message->markSent();
        $this->entityManager->flush();
    }
}
