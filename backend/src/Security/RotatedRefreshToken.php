<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class RotatedRefreshToken
{
    public function __construct(
        public User $user,
        public Cookie $cookie,
    ) {
    }
}
