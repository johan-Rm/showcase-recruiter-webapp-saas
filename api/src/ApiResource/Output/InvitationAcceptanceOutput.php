<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use DateTimeImmutable;

/**
 * Contexte public minimal affiche sur la page d'acceptation d'une invitation entreprise.
 */
final readonly class InvitationAcceptanceOutput
{
    public function __construct(
        public string $email,
        public string $organizationName,
        public string $role,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
