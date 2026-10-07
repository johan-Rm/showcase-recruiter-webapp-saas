<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Jarvi\JarviCompanyWriterInterface;
use App\Domain\Jarvi\JarviProfileWriterInterface;
use App\Domain\Jarvi\JarviUpsertCompany;
use App\Domain\Jarvi\JarviUpsertProfile;
use App\Domain\Organization\OrganizationJarviCompanySynchronizer;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Enum\OrganizationRole;
use App\Enum\SyncStatus;
use Doctrine\Common\Collections\Collection;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;

/**
 * Sync Organization → Jarvi (tâche 023), writers mockés : payload company minimal, persistance
 * du companyId, push du contact déclarant et échec résilient. Aucun appel Jarvi réel.
 */
final class OrganizationJarviCompanySyncTest extends ApiTestCase
{
    public function testSyncPushesCompanyPayloadAndMarksSynced(): void
    {
        $organization = new Organization(
            'Atlas Recrut',
            legalName: 'Atlas Recrut SARL',
            city: 'Rabat',
            website: 'https://atlas.example',
        );
        $writer = $this->capturingWriter('jarvi-company-id');
        $profileWriter = new CapturingJarviProfileWriter('jarvi-profile-id');

        $projectId = '00000000-0000-4000-8000-000000000003';
        $ok = (new OrganizationJarviCompanySynchronizer(
            $writer,
            $profileWriter,
            new NullLogger(),
            enabled: true,
            recruiterProjectId: $projectId,
        ))->sync($organization);

        self::assertTrue($ok);
        $sent = $writer->last();
        self::assertNotNull($sent);
        self::assertSame('Atlas Recrut SARL', $sent->name);
        self::assertSame($projectId, $writer->attachedProjectId());
        self::assertSame('jarvi-company-id', $writer->attachedCompanyId());
        self::assertSame('jarvi-company-id', $organization->jarviCompanyId);
        self::assertSame(SyncStatus::SYNCED, $organization->jarviCompanySyncStatus);
        self::assertNotNull($organization->jarviCompanySyncedAt);
        // Sans recruteur admin, aucun contact n'est poussé.
        self::assertNull($profileWriter->last());
    }

    public function testSyncPushesDeclarantContactProfile(): void
    {
        $organization = new Organization('Atlas Recrut', legalName: 'Atlas Recrut SARL');
        $declarant = new Person('Omar', 'Alaoui', contactEmail: 'omar@atlas.example', linkedinUrl: 'https://linkedin.com/in/omar', country: 'MA', city: 'Rabat');
        $this->attachAdmin($organization, $declarant);

        $writer = $this->capturingWriter('jarvi-company-id');
        $profileWriter = new CapturingJarviProfileWriter('jarvi-profile-id');

        $ok = (new OrganizationJarviCompanySynchronizer(
            $writer,
            $profileWriter,
            new NullLogger(),
            enabled: true,
            recruiterProjectId: '00000000-0000-4000-8000-000000000003',
        ))->sync($organization);

        self::assertTrue($ok);
        $contact = $profileWriter->last();
        self::assertNotNull($contact);
        self::assertSame('omar@atlas.example', $contact->emailAddresses);
        self::assertSame('Omar', $contact->firstName);
        self::assertSame('Alaoui', $contact->lastName);
        self::assertSame('Rabat, MA', $contact->location);
        // Rattaché au projet recruteur (pas de lien company : non supporté en REST Jarvi).
        self::assertSame('00000000-0000-4000-8000-000000000003', $contact->projectId);
        // Ancrage idempotent sur Person.uuid (id == externalId).
        self::assertSame($declarant->getUuid()->toString(), $contact->externalId);
        self::assertSame($declarant->getUuid()->toString(), $contact->id);
    }

    public function testContactSkippedWhenDeclarantHasNoEmail(): void
    {
        $organization = new Organization('Atlas Recrut');
        $declarant = new Person('Omar', 'Alaoui');
        $this->attachAdmin($organization, $declarant);

        $writer = $this->capturingWriter('jarvi-company-id');
        $profileWriter = new CapturingJarviProfileWriter('jarvi-profile-id');

        $ok = (new OrganizationJarviCompanySynchronizer(
            $writer,
            $profileWriter,
            new NullLogger(),
            enabled: true,
            recruiterProjectId: '00000000-0000-4000-8000-000000000003',
        ))->sync($organization);

        self::assertTrue($ok);
        self::assertNull($profileWriter->last());
    }

    public function testDisabledSyncDoesNotCallWriter(): void
    {
        $organization = new Organization('Atlas Recrut');
        $writer = $this->capturingWriter('jarvi-company-id');
        $profileWriter = new CapturingJarviProfileWriter('jarvi-profile-id');

        $ok = (new OrganizationJarviCompanySynchronizer($writer, $profileWriter, new NullLogger(), enabled: false))->sync($organization);

        self::assertFalse($ok);
        self::assertNull($writer->last());
        self::assertNull($profileWriter->last());
        self::assertNull($organization->jarviCompanyId);
        self::assertSame(SyncStatus::PENDING, $organization->jarviCompanySyncStatus);
    }

    public function testSyncFailureMarksFailedAndDoesNotThrow(): void
    {
        $organization = new Organization('Atlas Recrut');
        $writer = new class implements JarviCompanyWriterInterface {
            public function upsert(JarviUpsertCompany $company): string
            {
                throw new RuntimeException('Jarvi indisponible');
            }

            public function attachToProject(string $projectId, string $companyId): void
            {
            }
        };

        $ok = (new OrganizationJarviCompanySynchronizer(
            $writer,
            new CapturingJarviProfileWriter('jarvi-profile-id'),
            new NullLogger(),
            enabled: true,
            recruiterProjectId: '00000000-0000-4000-8000-000000000003',
        ))->sync($organization);

        self::assertFalse($ok);
        self::assertSame(SyncStatus::FAILED, $organization->jarviCompanySyncStatus);
        self::assertSame('Jarvi indisponible', $organization->jarviCompanySyncError);
    }

    public function testProjectAttachFailureKeepsReturnedCompanyId(): void
    {
        $organization = new Organization('Atlas Recrut');
        $writer = new class implements JarviCompanyWriterInterface {
            public function upsert(JarviUpsertCompany $company): string
            {
                return 'jarvi-company-id';
            }

            public function attachToProject(string $projectId, string $companyId): void
            {
                throw new RuntimeException('Projet Jarvi indisponible');
            }
        };

        $ok = (new OrganizationJarviCompanySynchronizer(
            $writer,
            new CapturingJarviProfileWriter('jarvi-profile-id'),
            new NullLogger(),
            enabled: true,
            recruiterProjectId: '00000000-0000-4000-8000-000000000003',
        ))->sync($organization);

        self::assertFalse($ok);
        self::assertSame('jarvi-company-id', $organization->jarviCompanyId);
        self::assertSame(SyncStatus::FAILED, $organization->jarviCompanySyncStatus);
        self::assertSame('Projet Jarvi indisponible', $organization->jarviCompanySyncError);
    }

    private function capturingWriter(string $companyId): CapturingJarviCompanyWriter
    {
        return new CapturingJarviCompanyWriter($companyId);
    }

    /**
     * Rattache un Person comme recruteur COMPANY_OWNER à l'Organization. La collection `recruiters`
     * étant la face inverse (peuplée par Doctrine en BDD), on l'alimente ici par réflexion pour rester
     * en test unitaire sans persistance.
     */
    private function attachAdmin(Organization $organization, Person $person): void
    {
        $recruiter = new Recruiter($person, $organization, OrganizationRole::COMPANY_OWNER);
        /** @var Collection<int, Recruiter> $collection */
        $collection = (new ReflectionProperty(Organization::class, 'recruiters'))->getValue($organization);
        $collection->add($recruiter);
    }
}

final class CapturingJarviCompanyWriter implements JarviCompanyWriterInterface
{
    private ?JarviUpsertCompany $last = null;
    private ?string $attachedProjectId = null;
    private ?string $attachedCompanyId = null;

    public function __construct(private readonly string $companyId)
    {
    }

    public function upsert(JarviUpsertCompany $company): string
    {
        $this->last = $company;

        return $this->companyId;
    }

    public function last(): ?JarviUpsertCompany
    {
        return $this->last;
    }

    public function attachToProject(string $projectId, string $companyId): void
    {
        $this->attachedProjectId = $projectId;
        $this->attachedCompanyId = $companyId;
    }

    public function attachedProjectId(): ?string
    {
        return $this->attachedProjectId;
    }

    public function attachedCompanyId(): ?string
    {
        return $this->attachedCompanyId;
    }
}

final class CapturingJarviProfileWriter implements JarviProfileWriterInterface
{
    private ?JarviUpsertProfile $last = null;

    public function __construct(private readonly string $profileId)
    {
    }

    public function upsert(JarviUpsertProfile $profile): string
    {
        $this->last = $profile;

        return $this->profileId;
    }

    public function last(): ?JarviUpsertProfile
    {
        return $this->last;
    }
}
