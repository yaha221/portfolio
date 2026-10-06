<?php

declare(strict_types=1);

namespace App\Dto\Request;

use OpenApi\Attributes as OA;

/**
 * Login body. Only documents the request: json_login in security.yaml reads it.
 */
final readonly class LoginRequest
{
    public function __construct(
        #[OA\Property(format: 'email', example: 'admin@portfolio.local')]
        public string $email,
        #[OA\Property(format: 'password')]
        public string $password,
    ) {
    }
}
