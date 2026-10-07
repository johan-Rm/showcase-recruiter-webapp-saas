<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Payload d'écriture ponctuelle vers Jarvi, forme write de `POST /profiles` (upsert).
 *
 * `location` est une chaîne « Ville, Pays » (Jarvi géocode lui-même). Les différenciants showcase-recruiter-webapp-saas
 * ne sont pas portés par ce payload.
 */
final readonly class JarviUpsertProfile
{
    public function __construct(
        public string $id,
        public string $externalId,
        public string $firstName,
        public string $lastName,
        public ?string $linkedinUrl = null,
        public ?string $location = null,
        public ?string $projectId = null,
        public ?string $emailAddresses = null,
    ) {
    }
}
