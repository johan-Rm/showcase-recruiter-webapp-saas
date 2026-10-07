<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Candidate\CandidateExposurePolicy;
use App\Entity\Candidate;
use App\Entity\Favorite;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La liste de rétention d'une recherche : les FAVORIS (task 073, réduite par la task 154).
 *
 * Le produit n'a plus qu'une liste — on retient ses top profils dans la Visionneuse, on dévoile et
 * on exporte depuis la Table. Le favori accepte donc un profil MASQUÉ : c'est le filet large, il se
 * pose avant de payer.
 */
final readonly class SearchListWriter
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateExposurePolicy $exposurePolicy,
    ) {
    }

    /**
     * Le filet large : un profil masqué est un favori parfaitement valide.
     *
     * Idempotent — (search, candidate) est unique.
     */
    public function addFavorite(SavedSearch $search, Candidate $candidate, Recruiter $addedBy): Favorite
    {
        $repository = $this->em->getRepository(Favorite::class);
        $existing = $repository->findOneBy(['search' => $search, 'candidate' => $candidate]);
        if ($existing instanceof Favorite) {
            return $existing;
        }

        $favorite = new Favorite($search, $candidate, $addedBy);
        $this->em->persist($favorite);
        $this->em->flush();

        return $favorite;
    }

    /**
     * Un profil sorti du périmètre Jarvi reste DANS les listes (historique préservé), mais aucune
     * nouvelle entrée n'est possible — règle « candidat exposable » (task 065).
     */
    public function isAddable(Candidate $candidate): bool
    {
        return $this->exposurePolicy->isExposable($candidate);
    }

    public function removeFavorite(SavedSearch $search, Candidate $candidate): void
    {
        $favorite = $this->em->getRepository(Favorite::class)
            ->findOneBy(['search' => $search, 'candidate' => $candidate]);
        if ($favorite instanceof Favorite) {
            $this->em->remove($favorite);
            $this->em->flush();
        }
    }
}
