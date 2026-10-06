<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Entity\ContactMessage;
use App\Enum\ContactMessageStatus;
use DateTimeInterface;
use OpenApi\Attributes as OA;

final readonly class ContactMessageView
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $message,
        #[OA\Property(description: 'Отправлено ли уведомление админу', enum: ContactMessageStatus::class)]
        public string $status,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
    ) {
    }

    public static function fromEntity(ContactMessage $message): self
    {
        return new self(
            (int) $message->getId(),
            $message->getName(),
            $message->getEmail(),
            $message->getMessage(),
            $message->getStatus()->value,
            $message->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
