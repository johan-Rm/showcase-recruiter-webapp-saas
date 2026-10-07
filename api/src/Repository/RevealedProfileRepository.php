<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Candidate;
use App\Entity\Organization;
use App\Entity\RevealedProfile;
use App\Enum\RevealType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Source de vérité du dévoilement : « ce candidat est-il dévoilé POUR CETTE ORGANISATION ? ».
 *
 * Le dévoilement est commun à l'entreprise (contrairement aux recherches, privées par défaut) :
 * c'est l'organisation qui a payé, pas le recruteur.
 *
 * @extends ServiceEntityRepository<RevealedProfile>
 */
final class RevealedProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RevealedProfile::class);
    }

    public function isRevealed(Organization $organization, Candidate $candidate, RevealType $type): bool
    {
        return null !== $this->findOneBy([
            'organization' => $organization,
            'candidate' => $candidate,
            'type' => $type,
        ]);
    }

    /**
     * Ce que l'organisation a payé, candidat par candidat — une requête pour toute une page.
     *
     * Indexé par id interne puis par valeur de type : l'appelant teste l'appartenance sans
     * reparcourir la liste, et un candidat absent signifie « rien de payé ».
     *
     * @return array<int, array<string, true>>
     */
    public function revealedTypesByCandidate(Organization $organization): array
    {
        $access = [];
        foreach ($this->findBy(['organization' => $organization]) as $revealed) {
            $access[(int) $revealed->candidate->getId()][$revealed->type->value] = true;
        }

        return $access;
    }
}
