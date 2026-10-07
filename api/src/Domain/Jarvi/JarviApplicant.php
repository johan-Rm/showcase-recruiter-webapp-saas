<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Payload d'écriture montante vers `POST /applicants` — le seul canal Jarvi qui déclenche
 * l'extraction du profil (headline immédiat, puis positions/educations/skills/summary en
 * asynchrone) à partir de la `linkedinUrl` et/ou d'un CV (`resumesFiles`, `source: RESUME`).
 *
 * Distinct de {@see JarviUpsertProfile} (`POST /profiles`, sync entreprise) qui n'extrait rien.
 * `location` est une chaîne « Ville, Pays » (Jarvi géocode). `externalId` porte l'ancre stable
 * showcase-recruiter-webapp-saas (`Person.uuid`) pour un upsert idempotent côté Jarvi.
 */
final readonly class JarviApplicant
{
    /**
     * @param list<JarviResumeFile> $resumesFiles
     */
    public function __construct(
        public string $externalId,
        public string $firstName,
        public string $lastName,
        public string $projectId,
        public ?string $linkedinUrl = null,
        public ?string $emailAddresses = null,
        public ?string $phoneNumbers = null,
        public ?string $headline = null,
        public ?string $location = null,
        public array $resumesFiles = [],
    ) {
    }
}
