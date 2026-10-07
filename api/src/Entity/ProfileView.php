<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trace une consultation de profil (≠ déblocage). Alimente les stats candidat et l'historique
 * admin. Append-only. Cf. data-model.md § ProfileView.
 */
#[ORM\Entity]
#[ORM\Table(name: 'profile_view')]
class ProfileView extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false)]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'recruiter_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $recruiter,
    ) {
        parent::__construct();
    }
}
