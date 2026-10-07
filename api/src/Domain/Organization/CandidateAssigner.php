<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Entity\Candidate;
use App\Entity\CandidateAssignment;
use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Enum\RecruiterStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Invariants métier de l'assignation : responsable dans la même entreprise active,
 * candidat déjà dévoilé par cette entreprise, unicité organisation/candidat.
 */
final readonly class CandidateAssigner
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function assign(
        Organization $organization,
        Candidate $candidate,
        Recruiter $assignee,
        Recruiter $assignedBy,
    ): CandidateAssignment {
        if ($assignee->organization !== $organization || RecruiterStatus::ACTIVE !== $assignee->status) {
            throw new AssigneeNotInOrganizationException('Le recruteur assigné doit être actif dans cette organisation.');
        }

        $revealed = $this->em->getRepository(RevealedProfile::class)->findOneBy([
            'organization' => $organization,
            'candidate' => $candidate,
        ]);
        if (!$revealed instanceof RevealedProfile) {
            throw new CandidateNotRevealedException('Le candidat doit être dévoilé avant assignation.');
        }

        $repository = $this->em->getRepository(CandidateAssignment::class);
        $assignment = $repository->findOneBy(['organization' => $organization, 'candidate' => $candidate]);
        if ($assignment instanceof CandidateAssignment) {
            $assignment->reassign($assignee, $assignedBy);

            return $assignment;
        }

        $assignment = new CandidateAssignment($organization, $candidate, $assignee, $assignedBy);
        $this->em->persist($assignment);

        return $assignment;
    }
}
