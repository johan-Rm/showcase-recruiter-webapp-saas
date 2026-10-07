<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SubscriptionStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Abonnement d'une Organization (0..1). Détail Stripe (plans, options) à cadrer ultérieurement.
 * Cf. data-model.md § Onboarding & facturation.
 */
#[ORM\Entity]
#[ORM\Table(name: 'subscription')]
class Subscription extends AbstractEntity
{
    public function __construct(
        #[ORM\OneToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, unique: true)]
        private Organization $organization,
        #[ORM\Column(length: 100)]
        public string $plan,
        #[ORM\Column(enumType: SubscriptionStatus::class)]
        public SubscriptionStatus $status = SubscriptionStatus::ACTIVE,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $currentPeriodEnd = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $stripeSubscriptionId = null,
    ) {
        parent::__construct();
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
