<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Payload showcase-recruiter-webapp-saas → Jarvi `POST /companies` (tâche 023).
 */
final readonly class JarviUpsertCompany
{
    public function __construct(
        public string $name,
        public ?string $linkedinUrl = null,
        public ?string $companyId = null,
    ) {
    }
}
