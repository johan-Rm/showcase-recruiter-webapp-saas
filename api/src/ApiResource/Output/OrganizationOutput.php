<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\OrganizationInput;
use App\Enum\AcquisitionChannel;
use App\Enum\OrganizationValidationStatus;
use App\State\Processor\MemberRemovalProcessor;
use App\State\Processor\OrganizationProcessor;
use App\State\Processor\OrganizationValidationProcessor;
use App\State\Provider\OrganizationItemProvider;
use App\State\Provider\OrganizationMembersProvider;
use App\State\Provider\OrganizationReviewProvider;
use DateTimeImmutable;

/**
 * Fiche entreprise (réponse plate). Création réservée à un user sans entreprise (devient ROLE_COMPANY_OWNER),
 * lecture/édition soumises au voter multi-tenant. Cf. data-model.md / api-routes.md.
 */
#[ApiResource(
    shortName: 'Organization',
    operations: [
        new Post(
            uriTemplate: '/organizations',
            input: OrganizationInput::class,
            validationContext: ['groups' => ['Default', 'create']],
            processor: OrganizationProcessor::class,
        ),
        new Get(
            uriTemplate: '/organizations/{id}',
            uriVariables: ['id'],
            provider: OrganizationItemProvider::class,
        ),
        new Put(
            uriTemplate: '/organizations/{id}',
            uriVariables: ['id'],
            input: OrganizationInput::class,
            provider: OrganizationItemProvider::class,
            processor: OrganizationProcessor::class,
        ),
        new Get(
            uriTemplate: '/admin/organizations',
            output: CollectionOutput::class,
            name: 'admin_organizations_review',
            provider: OrganizationReviewProvider::class,
            paginationEnabled: false,
            read: true,
        ),
        new Get(
            uriTemplate: '/organizations/{id}/members',
            uriVariables: ['id'],
            output: CollectionOutput::class,
            provider: OrganizationMembersProvider::class,
        ),
        new Post(
            uriTemplate: '/organizations/{id}/validate',
            uriVariables: ['id'],
            read: false,
            deserialize: false,
            name: 'organization_validate',
            processor: OrganizationValidationProcessor::class,
        ),
        new Post(
            uriTemplate: '/organizations/{id}/reject',
            uriVariables: ['id'],
            read: false,
            deserialize: false,
            name: 'organization_reject',
            processor: OrganizationValidationProcessor::class,
        ),
        new Delete(
            uriTemplate: '/organizations/{id}/members/{recruiterId}',
            uriVariables: ['id', 'recruiterId'],
            read: false,
            processor: MemberRemovalProcessor::class,
        ),
    ],
)]
final class OrganizationOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $legalName,
        public ?string $taxId,
        public ?string $country,
        public ?string $city,
        public ?string $billingAddress,
        public ?string $website,
        public ?string $logo,
        public ?string $sector,
        public ?string $description,
        // Enums typés (pas des `string`) : le contrat OpenAPI porte alors la liste des valeurs, et le
        // front la DÉRIVE au lieu de la recopier. Le sérialiseur les rend par leur valeur.
        public ?AcquisitionChannel $acquisitionChannel,
        public ?string $acquisitionChannelDetail,
        public OrganizationValidationStatus $validationStatus,
        public ?string $jarviCompanyId,
        public string $jarviCompanySyncStatus,
        public ?DateTimeImmutable $jarviCompanySyncedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
