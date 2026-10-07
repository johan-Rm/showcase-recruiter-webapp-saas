<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

final class MagicLinkConsumeOutput
{
    public function __construct(
        public string $token,
        public string $email,
        public string $userId,
    ) {
    }
}
