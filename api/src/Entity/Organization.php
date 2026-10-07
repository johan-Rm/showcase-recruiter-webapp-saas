<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AcquisitionChannel;
use App\Enum\OrganizationValidationStatus;
use App\Enum\SyncStatus;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * 🌐 schema.org Organization. L'entreprise marocaine — entité de facturation (Stripe customer).
 * Crédits, profils dévoilés et favoris sont partagés au niveau de l'Organization. Cf. data-model.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'organization')]
class Organization extends AbstractEntity
{
    /** @var Collection<int, Recruiter> */
    #[ORM\OneToMany(targetEntity: Recruiter::class, mappedBy: 'organization')]
    private Collection $recruiters;

    public function __construct(
        #[ORM\Column(length: 255)]
        public string $name,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $legalName = null,
        #[ORM\Column(length: 50, nullable: true)]
        public ?string $taxId = null,
        #[ORM\Column(length: 2, nullable: true)]
        public ?string $country = 'MA',
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $city = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $billingAddress = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $logo = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $website = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $stripeCustomerId = null,
        #[ORM\Column(length: 120, nullable: true)]
        public ?string $sector = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $description = null,
        // Provenance : figée à l'inscription (cf. cadrage inscription.md), jamais rééditable ensuite.
        #[ORM\Column(enumType: AcquisitionChannel::class, nullable: true)]
        public ?AcquisitionChannel $acquisitionChannel = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $acquisitionChannelDetail = null,
        /*
         * D'où le déclarant est ARRIVÉ, mesuré au premier contact — à ne pas confondre avec la
         * provenance ci-dessus, qu'il DÉCLARE. Les deux ne répondent pas à la même question et ne
         * doivent jamais se remplacer : « comment nous avez-vous connus ? » vs « par où êtes-vous
         * passé ? ». Une recommandation qui cherche « showcase-recruiter-webapp-saas » sur Google produit un referrer
         * `google.com` — il écraserait le seul canal qui avait de la valeur pour la revue.
         *
         * Origine seule, jamais l'URL complète : une URL de recherche transporterait la requête
         * tapée, donc potentiellement des données personnelles.
         *
         * Partiel par nature : vide depuis une application, un client mail ou une URL tapée.
         */
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $acquisitionReferrer = null,
        #[ORM\Column(enumType: OrganizationValidationStatus::class)]
        public OrganizationValidationStatus $validationStatus = OrganizationValidationStatus::EN_REVUE,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $jarviCompanyId = null,
        #[ORM\Column(enumType: SyncStatus::class)]
        public SyncStatus $jarviCompanySyncStatus = SyncStatus::PENDING,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $jarviCompanySyncedAt = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $jarviCompanySyncError = null,
    ) {
        parent::__construct();
        $this->recruiters = new ArrayCollection();
    }

    /** @return Collection<int, Recruiter> */
    public function getRecruiters(): Collection
    {
        return $this->recruiters;
    }

    public function isValidated(): bool
    {
        return OrganizationValidationStatus::VALIDEE === $this->validationStatus;
    }

    /**
     * La revue ne garde plus la porte : l'accès s'ouvre à l'inscription et c'est le REFUS qui le
     * coupe. Les gardes interrogent donc ceci, jamais `isValidated()` — une entreprise en cours de
     * revue travaille normalement.
     */
    public function isRejected(): bool
    {
        return OrganizationValidationStatus::REFUSE === $this->validationStatus;
    }
}
