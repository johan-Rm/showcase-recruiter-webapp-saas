<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminCandidateProvider;
use DateTimeImmutable;

/**
 * Carte candidat pour l'espace ADMIN : le vivier cross-tenant vu par l'opérateur de la plateforme,
 * en clair (aucun masquage — c'est le pendant « tout dévoilé » de CandidateSearch) et enrichi des
 * champs d'exploitation absents de la carte recruteur : visibilité, activité, consentement, statut
 * de synchro. Enveloppe plate paginée (CollectionOutput). Lecture seule, gardée ROLE_ADMIN.
 */
#[ApiResource(
    shortName: 'AdminCandidate',
    operations: [
        new Get(
            uriTemplate: '/admin/candidates',
            output: CollectionOutput::class,
            name: 'admin_candidates',
            provider: AdminCandidateProvider::class,
            paginationEnabled: false,
            read: true,
        ),
    ],
)]
final class AdminCandidateOutput
{
    /** @param list<string> $skills */
    public function __construct(
        public string $id,
        public string $givenName,
        public string $familyName,
        /** Photo : URL publique Jarvi ; vue admin non masquée, servie dès qu'elle existe. */
        public ?string $photoUrl,
        public ?string $headline,
        public int $experienceYears,
        public ?string $city,
        public ?string $country,
        public ?string $contactEmail,
        public ?string $phone,
        public ?string $linkedinUrl,
        public ?string $desiredSector,
        public ?string $desiredFunction,
        public ?string $availability,
        public ?string $returnHorizon,
        public bool $geoMobility,
        public bool $hasPiedATerre,
        public ?string $piedATerreCity,
        public array $skills,
        public bool $isStar,
        public string $visibility,
        public bool $active,
        public bool $hasConsent,
        public ?DateTimeImmutable $consentGivenAt,
        public string $syncStatus,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
