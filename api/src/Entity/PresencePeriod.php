<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * ★ Donnée différenciante showcase-recruiter-webapp-saas : dates auxquelles le candidat sera physiquement au Maroc
 * (dispo entretien, pas un voyage). Donnée locale showcase-recruiter-webapp-saas utilisée par la recherche entreprise.
 * Édition self-service (tâche 017) ; CHECK `endDate >= startDate` garanti en base (migration prod,
 * convention projet) + validé à l'écriture. Cf. data-model.md / data-integrity.md § PresencePeriod.
 */
#[ORM\Entity]
#[ORM\Table(name: 'presence_period')]
class PresencePeriod extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class, inversedBy: 'presencePeriods')]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private Candidate $candidate,
        #[ORM\Column(type: 'date_immutable')]
        public DateTimeImmutable $startDate,
        #[ORM\Column(type: 'date_immutable')]
        public DateTimeImmutable $endDate,
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $city = null,
    ) {
        parent::__construct();
    }

    public function getCandidate(): Candidate
    {
        return $this->candidate;
    }
}
