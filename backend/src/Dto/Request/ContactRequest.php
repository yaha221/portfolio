<?php

declare(strict_types=1);

namespace App\Dto\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactRequest
{
    public function __construct(
        #[OA\Property(example: 'Иван')]
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 100)]
        public string $name,
        #[OA\Property(format: 'email', example: 'ivan@example.com')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        #[Assert\Email]
        public string $email,
        #[OA\Property(example: 'Здравствуйте! Хочу обсудить проект.')]
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(min: 10, max: 5000)]
        public string $message,
        #[OA\Property(description: 'Ловушка для ботов: скрытое поле формы, должно оставаться пустым')]
        public string $website = '',
    ) {
    }
}
