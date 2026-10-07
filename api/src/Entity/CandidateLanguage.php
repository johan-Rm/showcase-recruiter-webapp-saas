<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LanguageProficiency;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une langue parlée par un candidat, avec son niveau — DÉRIVÉE des `locales` Jarvi à l'ingestion
 * (task 114).
 *
 * Table normalisée plutôt qu'un JSON sur la réplica : c'est ce qui rend la langue **filtrable** en
 * DQL (jointure simple), sans fonction JSON dédiée — même choix que l'expérience par secteur.
 *
 * `proficiency` est **nullable** : le niveau manque sur environ un tiers des entrées du vivier, et
 * on ne le suppose pas. Champ DÉRIVÉ, recalculé à chaque réingestion.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_language')]
#[ORM\UniqueConstraint(name: 'uniq_candidate_language', columns: ['candidate_id', 'language'])]
#[ORM\Index(name: 'idx_candidate_language_language', columns: ['language'])]
class CandidateLanguage extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class, inversedBy: 'languages')]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Candidate $candidate,
        #[ORM\Column(length: 8)]
        public string $language,
        #[ORM\Column(enumType: LanguageProficiency::class, nullable: true)]
        public ?LanguageProficiency $proficiency = null,
    ) {
        parent::__construct();
    }
}
