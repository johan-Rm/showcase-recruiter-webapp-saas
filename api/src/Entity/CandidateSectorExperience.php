<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Sector;
use Doctrine\ORM\Mapping as ORM;

/**
 * Expérience cumulée d'un candidat dans UN secteur, en mois — dérivée à l'ingestion du parcours et
 * du référentiel entreprise → secteur (task 095).
 *
 * Table normalisée plutôt qu'un JSON sur la réplica : c'est ce qui rend le secteur **filtrable** en
 * DQL (jointure simple) et permet les compteurs de facette, sans fonction JSON dédiée.
 *
 * Champ DÉRIVÉ : intégralement recalculé à chaque réingestion, jamais saisi.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_sector_experience')]
#[ORM\UniqueConstraint(name: 'uniq_candidate_sector', columns: ['candidate_id', 'sector'])]
#[ORM\Index(name: 'idx_candidate_sector_sector', columns: ['sector'])]
class CandidateSectorExperience extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class, inversedBy: 'sectorExperiences')]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Candidate $candidate,
        #[ORM\Column(enumType: Sector::class)]
        public Sector $sector,
        #[ORM\Column(type: 'integer')]
        public int $months = 0,
    ) {
        parent::__construct();
    }
}
