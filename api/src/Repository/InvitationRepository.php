<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invitation;
use App\Entity\Organization;
use App\Enum\InvitationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invitation>
 */
final class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    public function findOneByToken(string $token): ?Invitation
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Invitation PENDING déjà émise pour ce couple (organization, email) — sert de garde-fou anti-doublon.
     */
    public function findPending(Organization $organization, string $email): ?Invitation
    {
        return $this->findOneBy([
            'organization' => $organization,
            'email' => $email,
            'status' => InvitationStatus::PENDING,
        ]);
    }

    /**
     * @return list<Invitation>
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->findBy(['organization' => $organization], ['createdAt' => 'DESC', 'id' => 'DESC']);
    }
}
