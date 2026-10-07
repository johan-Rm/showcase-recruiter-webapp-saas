<?php

declare(strict_types=1);

namespace App\Domain\Auth;

final readonly class MagicLinkAuthenticatedUser
{
    public function __construct(
        public string $token,
        public string $email,
        public string $userId,
    ) {
    }
}
