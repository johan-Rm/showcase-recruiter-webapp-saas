<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Métier exercé par un candidat et durée cumulée associée — DÉRIVÉ à l'ingestion du parcours et du
 * vocabulaire des postes (task 095).
 *
 * Table normalisée plutôt qu'un JSON sur la réplica, pour les mêmes raisons que l'expérience par
 * secteur : filtrable en DQL et comptable en facette.
 *
 * **Le signal fiable est la PRÉSENCE de la ligne**, pas `months` : le rattachement des intitulés est
 * partiel (médiane de 33 % du parcours), donc la durée sous-estime. `positions` dit combien de
 * postes l'ont alimentée — c'est ce qui rend l'approximation lisible au lieu de la masquer. Ne pas
 * bâtir de filtre « au moins N années de ce métier » là-dessus.
 *
 * Champ DÉRIVÉ : intégralement recalculé à chaque réingestion, jamais saisi.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_job_experience')]
#[ORM\UniqueConstraint(name: 'uniq_candidate_job', columns: ['candidate_id', 'concept_id'])]
#[ORM\Index(name: 'idx_candidate_job_concept', columns: ['concept_id'])]
class CandidateJobExperience extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class, inversedBy: 'jobExperiences')]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: VocabularyConcept::class)]
        #[ORM\JoinColumn(name: 'concept_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public VocabularyConcept $concept,
        /** Durée cumulée, INDICATIVE : sous-estimée dès qu'un intitulé du parcours sort du dictionnaire. */
        #[ORM\Column(type: 'integer')]
        public int $months = 0,
        /** Postes du parcours ayant alimenté cette ligne — rend visible la part réellement rattachée. */
        #[ORM\Column(type: 'integer')]
        public int $positions = 0,
        /**
         * Parmi eux, ceux reconnus par ÉGALITÉ de libellé. Les autres viennent de la reconnaissance
         * partielle (task 118), qui élargit beaucoup la couverture mais se trompe dans ~7 % des cas :
         * sans cette trace, les deux qualités se confondraient dans un même chiffre.
         */
        #[ORM\Column(type: 'integer')]
        public int $exactPositions = 0,
    ) {
        parent::__construct();
    }
}
