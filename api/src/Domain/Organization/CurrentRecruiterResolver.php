<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\RecruiterStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Résolution centralisée du recruteur courant : l'existence d'une ligne Recruiter ne suffit pas,
 * elle doit aussi être active.
 */
final readonly class CurrentRecruiterResolver
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function resolveForOrganization(User $user, Organization $organization): ?Recruiter
    {
        $recruiter = $this->em->getRepository(Recruiter::class)->findOneBy([
            'person' => $user->getPerson(),
            'organization' => $organization,
            'status' => RecruiterStatus::ACTIVE,
        ]);

        return $recruiter instanceof Recruiter ? $recruiter : null;
    }

    public function resolveFirstActive(User $user): ?Recruiter
    {
        $recruiter = $this->em->getRepository(Recruiter::class)->findOneBy([
            'person' => $user->getPerson(),
            'status' => RecruiterStatus::ACTIVE,
        ]);

        return $recruiter instanceof Recruiter ? $recruiter : null;
    }
}
