<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Solde de crédits courant d'une Organization (1-1). Cache dérivé du journal CreditTransaction
 * (source de vérité). CHECK balance >= 0 garanti en base. Cf. data-model.md + data-integrity.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'credit_wallet')]
class CreditWallet extends AbstractEntity
{
    public function __construct(
        #[ORM\OneToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, unique: true)]
        private Organization $organization,
        #[ORM\Column(type: 'integer', options: ['default' => 0])]
        public int $balance = 0,
    ) {
        parent::__construct();
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
