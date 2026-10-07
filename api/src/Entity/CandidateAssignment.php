<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Responsable recruteur affecté à un candidat dévoilé par l'entreprise.
 * Un seul responsable par couple (organization, candidate) en V1.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_assignment')]
#[ORM\UniqueConstraint(name: 'uniq_assignment_organization_candidate', columns: ['organization_id', 'candidate_id'])]
class CandidateAssignment extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false)]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'assignee_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $assignee,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'assigned_by_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $assignedBy,
        #[ORM\Column]
        public DateTimeImmutable $assignedAt = new DateTimeImmutable(),
    ) {
        parent::__construct();
    }

    public function reassign(Recruiter $assignee, Recruiter $assignedBy): void
    {
        $this->assignee = $assignee;
        $this->assignedBy = $assignedBy;
        $this->assignedAt = new DateTimeImmutable();
    }
}
