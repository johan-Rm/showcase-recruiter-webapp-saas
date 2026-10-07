<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Enum\AcquisitionChannel;

/**
 * Données d'entrée (déjà validées) pour créer/mettre à jour une Organization, découplées de la
 * couche HTTP (le State Processor mappe l'Input DTO vers ce draft Domain).
 */
final readonly class OrganizationDraft
{
    public function __construct(
        public string $name,
        public string $givenName,
        public string $familyName,
        public ?string $legalName = null,
        public ?string $taxId = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?string $billingAddress = null,
        public ?string $website = null,
        public ?string $sector = null,
        public ?string $description = null,
        public ?string $jobTitle = null,
        public ?AcquisitionChannel $acquisitionChannel = null,
        public ?string $acquisitionChannelDetail = null,
        /** Mesuré, pas déclaré : voir Organization::$acquisitionReferrer. */
        public ?string $acquisitionReferrer = null,
    ) {
    }
}
