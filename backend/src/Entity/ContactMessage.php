<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ContactMessageStatus;
use App\Repository\ContactMessageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
// Matches the admin list's ORDER BY created_at DESC, id DESC exactly (no extra sort step)
#[ORM\Index(name: 'contact_message_created_at_id_idx', columns: ['created_at', 'id'])]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    #[ORM\Column(length: 16, enumType: ContactMessageStatus::class)]
    private ContactMessageStatus $status = ContactMessageStatus::New;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(string $name, string $email, string $message)
    {
        $this->name = $name;
        $this->email = $email;
        $this->message = $message;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatus(): ContactMessageStatus
    {
        return $this->status;
    }

    public function markSent(): void
    {
        $this->status = ContactMessageStatus::Sent;
    }

    public function markFailed(): void
    {
        $this->status = ContactMessageStatus::Failed;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
