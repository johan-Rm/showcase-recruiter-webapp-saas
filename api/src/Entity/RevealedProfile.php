<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RevealType;
use Doctrine\ORM\Mapping as ORM;

/**
 * Déblocage d'un ACCÈS à un candidat par une entreprise (espace « déjà dévoilés » commun).
 *
 * Une ligne par accès payé — coordonnées, CV, compte rendu (task 155) : l'unicité porte donc sur
 * (organization, candidate, type), et non plus sur le seul couple. On trace le recruteur.
 * Cf. data-model.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'revealed_profile')]
#[ORM\UniqueConstraint(name: 'uniq_revealed_organization_candidate_type', columns: ['organization_id', 'candidate_id', 'type'])]
class RevealedProfile extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false)]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'revealed_by_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $revealedBy,
        // Aucun défaut : depuis que les accès sont quatre et qu'aucun ne prime, en oublier un
        // n'écrit plus silencieusement le mauvais.
        #[ORM\Column(type: 'string', length: 32, enumType: RevealType::class)]
        public RevealType $type,
    ) {
        parent::__construct();
    }
}
