<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminCandidateItemProvider;
use DateTimeImmutable;

/**
 * Fiche candidat ADMIN (vue dédiée) : profil complet NON masqué (identité, coordonnées, employeurs,
 * CV, compte rendu d'entretien en clair) + réplica pro Jarvi (résumé, langues, expériences,
 * formations) + champs d'exploitation (visibilité, activité, consentement, synchro, ancre Jarvi).
 * Lecture seule, gardée ROLE_ADMIN. Le salaire reste hors périmètre (notion retirée du produit).
 */
#[ApiResource(
    shortName: 'AdminCandidateDetail',
    operations: [
        new Get(
            uriTemplate: '/admin/candidates/{id}',
            uriVariables: ['id'],
            provider: AdminCandidateItemProvider::class,
        ),
    ],
)]
final class AdminCandidateDetailOutput
{
    /**
     * @param list<string>                                                   $skills
     * @param list<array<string, mixed>>                                     $languages
     * @param list<array<string, mixed>>                                     $workExperiences
     * @param list<array<string, mixed>>                                     $educations
     * @param list<array<string, mixed>>                                     $resumes
     * @param list<array{startDate: string, endDate: string, city: ?string}> $presencePeriods
     */
    public function __construct(
        public string $id,
        public string $givenName,
        public string $familyName,
        /** Photo : URL publique Jarvi ; vue admin non masquée, servie dès qu'elle existe. */
        public ?string $photoUrl,
        public ?string $headline,
        public ?string $summary,
        public int $experienceYears,
        public ?string $city,
        public ?string $country,
        public ?string $contactEmail,
        public ?string $phone,
        /** Numéro joignable sur WhatsApp (E.164), dérivé du JID Jarvi. */
        public ?string $whatsappNumber,
        public ?string $linkedinUrl,
        public ?string $desiredSector,
        public ?string $desiredFunction,
        public ?string $availability,
        public ?string $returnHorizon,
        public ?string $returnProject,
        public bool $geoMobility,
        public bool $hasPiedATerre,
        public ?string $piedATerreCity,
        public array $skills,
        public array $languages,
        public array $workExperiences,
        public array $educations,
        public array $resumes,
        public array $presencePeriods,
        public bool $isStar,
        public ?DateTimeImmutable $starInterviewedAt,
        public ?string $starInterviewReport,
        public string $visibility,
        public bool $active,
        public bool $hasConsent,
        public ?DateTimeImmutable $consentGivenAt,
        public ?string $consentSource,
        public string $syncStatus,
        public string $jarviProfileId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
