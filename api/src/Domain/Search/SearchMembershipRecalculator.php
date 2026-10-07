<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Entity\Candidate;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Repository\CandidateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Recalcule l'appartenance des candidats au jeu de résultats des recherches (task 072).
 *
 * Appelé après chaque ingestion Jarvi : c'est le moment où un profil peut rejoindre une recherche
 * sans être nouveau (fenêtre de présence au Maroc, disponibilité, consentement…). Le badge `+N`
 * compte exactement les entrées que ce recalcul date.
 *
 * Diff par recherche : les ids matchants sont comparés aux lignes existantes. Les entrants
 * reçoivent `firstMatchedAt = now`, les sortants perdent leur ligne (revenir compte de nouveau
 * comme une entrée).
 */
final readonly class SearchMembershipRecalculator
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateRepository $candidates,
        private SearchCriteriaTranslator $translator,
    ) {
    }

    /** Recalcule toutes les recherches. @return int nombre d'entrées nouvellement matchées */
    public function recalculateAll(): int
    {
        $entered = 0;
        foreach ($this->em->getRepository(SavedSearch::class)->findAll() as $search) {
            $entered += $this->recalculate($search);
        }

        return $entered;
    }

    /** @return int nombre de candidats ayant REJOINT le jeu de résultats de cette recherche */
    public function recalculate(SavedSearch $search): int
    {
        // Une recherche sans critère matcherait le vivier entier : on ne matérialise rien tant
        // qu'aucun critère n'est posé (une recherche naît vide, au premier critère).
        if ([] === $search->criteria) {
            return 0;
        }

        $matchingIds = $this->candidates->matchingIds(
            $this->translator->toSearchCriteria($search->criteria),
        );
        $matching = array_fill_keys($matchingIds, true);

        $repository = $this->em->getRepository(SearchResultMembership::class);
        /** @var list<SearchResultMembership> $existingRows */
        $existingRows = $repository->findBy(['search' => $search]);

        $scores = $this->scores($search->criteria);

        $existing = [];
        foreach ($existingRows as $row) {
            $candidateId = (int) $row->candidate->getId();
            if (isset($matching[$candidateId])) {
                $existing[$candidateId] = true;
                // Le rang se refait à chaque passage : ajuster un critère change ce que chacun
                // satisfait, sans pour autant le faire entrer ni sortir du jeu.
                $row->matchedCount = $scores[$candidateId] ?? 0;
                continue;
            }
            $this->em->remove($row); // sorti du jeu de résultats
        }

        $now = new DateTimeImmutable();
        $entered = 0;
        foreach ($matchingIds as $candidateId) {
            if (isset($existing[$candidateId])) {
                continue;
            }
            $candidate = $this->em->getReference(Candidate::class, $candidateId);
            $this->em->persist(new SearchResultMembership($search, $candidate, $now, $scores[$candidateId] ?? 0));
            ++$entered;
        }

        $this->em->flush();

        return $entered;
    }

    /**
     * Combien de critères chaque profil satisfait — en rejouant le filtre **un critère à la fois**.
     *
     * C'est le point important : le rang n'est pas produit par une seconde implémentation des
     * règles, mais par CELLE QUI FILTRE. Un moteur et un score écrits séparément se contrediraient
     * au premier ajustement — le profil le mieux classé ne serait plus celui que la recherche
     * retient le mieux.
     *
     * Le coût est celui d'une recherche par critère, soit trois ou quatre en pratique, et non d'un
     * calcul par profil : une recherche qui ramène deux mille personnes en coûterait deux mille.
     *
     * @param list<array{facet: string, value: string, group: string}> $criteria
     *
     * @return array<int, int> nombre de critères satisfaits, indexé par id interne de candidat
     */
    private function scores(array $criteria): array
    {
        $scores = [];

        foreach ($criteria as $criterion) {
            foreach ($this->candidates->matchingIds($this->translator->toSearchCriteria([$criterion])) as $candidateId) {
                $scores[$candidateId] = ($scores[$candidateId] ?? 0) + 1;
            }
        }

        return $scores;
    }
}
