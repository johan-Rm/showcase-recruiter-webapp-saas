<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CreditTransactionType;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal des crédits — source de vérité du solde. Consommation idempotente, ne peut rendre le
 * solde négatif (cf. data-integrity.md, cœur sensible testé en tâche 003). CHECK amount <> 0.
 */
#[ORM\Entity]
#[ORM\Table(name: 'credit_transaction')]
class CreditTransaction extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\Column(enumType: CreditTransactionType::class)]
        public CreditTransactionType $type,
        #[ORM\Column(type: 'integer')]
        public int $amount,
        #[ORM\Column(type: 'integer')]
        public int $balanceAfter,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $reason = null,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'recruiter_id', referencedColumnName: 'id', nullable: true)]
        public ?Recruiter $recruiter = null,
        #[ORM\ManyToOne(targetEntity: RevealedProfile::class)]
        #[ORM\JoinColumn(name: 'revealed_profile_id', referencedColumnName: 'id', nullable: true)]
        public ?RevealedProfile $revealedProfile = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $stripePaymentId = null,
    ) {
        parent::__construct();
    }
}
