<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * Membre (recruteur) d'une entreprise — réponse plate, identité résolue depuis le Person.
 */
final class RecruiterOutput
{
    public function __construct(
        public string $id,
        public string $givenName,
        public string $familyName,
        public ?string $contactEmail,
        public string $organizationRole,
        public ?string $jobTitle,
        public string $status,
    ) {
    }
}
