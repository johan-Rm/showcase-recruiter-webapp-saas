<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Jarvi\JarviCompanyWriterInterface;
use App\Domain\Jarvi\JarviProfileWriterInterface;
use App\Domain\Jarvi\JarviUpsertCompany;
use App\Domain\Jarvi\JarviUpsertProfile;
use App\Entity\Organization;
use App\Entity\Person;
use App\Enum\OrganizationRole;
use App\Enum\SyncStatus;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Sync sortante Organization showcase-recruiter-webapp-saas → Jarvi.
 * Désactivée par défaut : aucun appel réseau Jarvi sans feature flag explicite.
 *
 * Pousse, dans l'ordre : la company (upsert + rattachement au projet recruteur), puis le **contact
 * déclarant** (COMPANY_OWNER) en profil Jarvi — `POST /profiles`, ancré sur `Person.uuid` (externalId)
 * pour un upsert idempotent et rattaché au **projet recruteur** (`projectId`). L'API REST Jarvi n'offre
 * pas de lien profil ↔ entreprise (testé : `currentCompanyId` ignoré, `currentCompanyName` crée une
 * expérience pro) → on s'en tient au rattachement projet.
 */
final readonly class OrganizationJarviCompanySynchronizer
{
    public function __construct(
        private JarviCompanyWriterInterface $writer,
        private JarviProfileWriterInterface $profileWriter,
        private LoggerInterface $logger,
        private bool $enabled = false,
        private string $recruiterProjectId = '',
    ) {
    }

    public function sync(Organization $organization): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            if ('' === $this->recruiterProjectId) {
                throw new RuntimeException('Projet Jarvi recruteur non configuré.');
            }

            $companyId = $this->writer->upsert($this->toUpsert($organization));
            $organization->jarviCompanyId = $companyId;
            $this->writer->attachToProject($this->recruiterProjectId, $companyId);
            $this->syncDeclarantContact($organization);
            $organization->jarviCompanySyncStatus = SyncStatus::SYNCED;
            $organization->jarviCompanySyncedAt = new DateTimeImmutable();
            $organization->jarviCompanySyncError = null;

            return true;
        } catch (Throwable $e) {
            $organization->jarviCompanySyncStatus = SyncStatus::FAILED;
            $organization->jarviCompanySyncError = $e->getMessage();
            $this->logger->error('Sync company Jarvi échouée', [
                'organizationId' => $organization->getUuid()->toString(),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Pousse le contact déclarant (COMPANY_OWNER) en profil Jarvi. No-op silencieux si l'entreprise
     * n'a pas d'owner ou si son e-mail est inconnu — l'absence de contact ne doit pas faire échouer
     * la synchro company déjà aboutie. Un échec réseau, lui, remonte (try/catch de `sync`).
     */
    private function syncDeclarantContact(Organization $organization): void
    {
        $declarant = $this->findDeclarant($organization);
        if (null === $declarant || null === $declarant->contactEmail || '' === $declarant->contactEmail) {
            return;
        }

        $this->profileWriter->upsert(new JarviUpsertProfile(
            id: $declarant->getUuid()->toString(),
            externalId: $declarant->getUuid()->toString(),
            firstName: $declarant->givenName,
            lastName: $declarant->familyName,
            linkedinUrl: $declarant->linkedinUrl,
            location: $this->location($declarant->city, $declarant->country),
            projectId: $this->recruiterProjectId,
            emailAddresses: $declarant->contactEmail,
        ));
    }

    private function findDeclarant(Organization $organization): ?Person
    {
        foreach ($organization->getRecruiters() as $recruiter) {
            if (OrganizationRole::COMPANY_OWNER === $recruiter->organizationRole) {
                return $recruiter->getPerson();
            }
        }

        return null;
    }

    private function location(?string $city, ?string $country): ?string
    {
        $parts = array_values(array_filter([$city, $country], static fn (?string $p): bool => null !== $p && '' !== $p));

        return [] === $parts ? null : implode(', ', $parts);
    }

    private function toUpsert(Organization $organization): JarviUpsertCompany
    {
        return new JarviUpsertCompany(
            $this->companyName($organization),
            null,
            $organization->jarviCompanyId,
        );
    }

    private function companyName(Organization $organization): string
    {
        return null !== $organization->legalName && '' !== $organization->legalName
            ? $organization->legalName
            : $organization->name;
    }
}
