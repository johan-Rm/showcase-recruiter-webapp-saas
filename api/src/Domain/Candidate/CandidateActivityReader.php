<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Entity\Candidate;
use App\Entity\ProfileView;
use App\Entity\RevealedProfile;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Agrège l'activité d'un candidat (tâche 019-A) : consultations, déblocages et entreprises
 * intéressées. Compteurs seuls — aucune identité d'entreprise n'est exposée (anonymisation par
 * construction ; la branche « identité visible selon option payante » du cadrage est reportée en V2).
 * Cf. dev-book/cadrage/candidat/vue_d_ensemble.md.
 */
final readonly class CandidateActivityReader
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{profileViews: int, reveals: int, interestedCompanies: int}
     */
    public function read(Candidate $candidate): array
    {
        return [
            'profileViews' => $this->count(ProfileView::class, $candidate),
            'reveals' => $this->count(RevealedProfile::class, $candidate),
            'interestedCompanies' => $this->countInterestedCompanies($candidate),
        ];
    }

    /**
     * Depuis la task 073, un favori appartient à une RECHERCHE, pas à l'entreprise : le même
     * candidat peut être favori dans plusieurs recherches d'une même entreprise. Compter les lignes
     * gonflerait le chiffre — on compte les ENTREPRISES distinctes, ce que le nom promet.
     */
    private function countInterestedCompanies(Candidate $candidate): int
    {
        $count = $this->entityManager
            ->createQuery(
                'SELECT COUNT(DISTINCT s.organization) FROM App\Entity\Favorite f JOIN f.search s WHERE f.candidate = :candidate'
            )
            ->setParameter('candidate', $candidate)
            ->getSingleScalarResult();

        return (int) $count;
    }

    /**
     * @param class-string $entityClass entité possédant une relation `candidate` (ManyToOne)
     */
    private function count(string $entityClass, Candidate $candidate): int
    {
        $count = $this->entityManager
            ->createQuery(\sprintf('SELECT COUNT(e.id) FROM %s e WHERE e.candidate = :candidate', $entityClass))
            ->setParameter('candidate', $candidate)
            ->getSingleScalarResult();

        return (int) $count;
    }
}
