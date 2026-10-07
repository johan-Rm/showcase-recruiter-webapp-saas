<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Note partagée sur un candidat au sein d'une entreprise (collaboration V1). Cf. data-model.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'profile_note')]
class ProfileNote extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false)]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $author,
        #[ORM\Column(type: 'text')]
        public string $body,
    ) {
        parent::__construct();
    }
}
