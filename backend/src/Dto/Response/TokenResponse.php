<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

final readonly class TokenResponse
{
    public function __construct(
        #[OA\Property(description: 'Access-токен (JWT) для заголовка Authorization: Bearer')]
        public string $token,
        #[OA\Property(description: 'Через сколько секунд токен истечёт', example: 900)]
        public int $expiresIn,
    ) {
    }
}
