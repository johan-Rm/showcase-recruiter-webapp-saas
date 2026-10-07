<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchProfileReview;
use App\Entity\SearchResultMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Stringable;

/**
 * L'avancement d'un recruteur sur les profils d'une recherche (task API-007).
 *
 * @extends ServiceEntityRepository<SearchProfileReview>
 */
class SearchProfileReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchProfileReview::class);
    }

    /**
     * Ce que ce recruteur a vu et écarté sur cette recherche, en UNE requête.
     *
     * Indexé par id interne de candidat : la page teste l'appartenance ligne par ligne, une requête
     * par ligne coûterait autant que la page entière.
     *
     * @return array{seen: array<int, true>, dismissed: array<int, true>}
     */
    public function statesFor(SavedSearch $search, Recruiter $recruiter): array
    {
        /** @var list<array{candidateId: int|string, seenAt: mixed, dismissedAt: mixed}> $rows */
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.candidate) AS candidateId', 'r.seenAt AS seenAt', 'r.dismissedAt AS dismissedAt')
            ->andWhere('r.search = :search')->setParameter('search', $search)
            ->andWhere('r.recruiter = :recruiter')->setParameter('recruiter', $recruiter)
            ->getQuery()
            ->getResult();

        $seen = [];
        $dismissed = [];
        foreach ($rows as $row) {
            $candidateId = (int) $row['candidateId'];
            if (null !== $row['seenAt']) {
                $seen[$candidateId] = true;
            }
            if (null !== $row['dismissedAt']) {
                $dismissed[$candidateId] = true;
            }
        }

        return ['seen' => $seen, 'dismissed' => $dismissed];
    }

    /**
     * Les lignes existantes pour ce lot, indexées par candidat — pour ne pas en créer de doublon.
     *
     * @return array<int, SearchProfileReview>
     */
    /**
     * Vus et écartés de PLUSIEURS recherches, en identifiants publics, pour UN lecteur.
     *
     * L'avancement est personnel : deux recruteurs qui partagent une recherche n'ont ni les mêmes
     * profils vus, ni les mêmes écartés. La requête est donc bornée au lecteur, et groupée sur les
     * recherches — une par recherche coûterait autant d'allers-retours que le plan en compte.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, array{seen: list<string>, dismissed: list<string>}> indexé par id interne de recherche
     */
    public function statesForMany(array $searches, Recruiter $recruiter): array
    {
        if ([] === $searches) {
            return [];
        }

        /** @var list<array{searchId: int|string, uuid: Stringable|string, seenAt: mixed, dismissedAt: mixed}> $rows */
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.search) AS searchId', 'c.uuid AS uuid', 'r.seenAt AS seenAt', 'r.dismissedAt AS dismissedAt')
            ->innerJoin('r.candidate', 'c')
            ->andWhere('r.search IN (:searches)')->setParameter('searches', $searches)
            ->andWhere('r.recruiter = :recruiter')->setParameter('recruiter', $recruiter)
            ->getQuery()
            ->getResult();

        $states = [];
        foreach ($rows as $row) {
            $searchId = (int) $row['searchId'];
            $states[$searchId] ??= ['seen' => [], 'dismissed' => []];

            if (null !== $row['seenAt']) {
                $states[$searchId]['seen'][] = (string) $row['uuid'];
            }
            if (null !== $row['dismissedAt']) {
                $states[$searchId]['dismissed'][] = (string) $row['uuid'];
            }
        }

        return $states;
    }

    public function existingFor(SavedSearch $search, Recruiter $recruiter, array $candidateIds): array
    {
        if ([] === $candidateIds) {
            return [];
        }

        /** @var list<SearchProfileReview> $rows */
        $rows = $this->createQueryBuilder('r')
            ->andWhere('r.search = :search')->setParameter('search', $search)
            ->andWhere('r.recruiter = :recruiter')->setParameter('recruiter', $recruiter)
            ->andWhere('IDENTITY(r.candidate) IN (:candidates)')->setParameter('candidates', $candidateIds)
            ->getQuery()
            ->getResult();

        $byCandidate = [];
        foreach ($rows as $row) {
            $byCandidate[(int) $row->candidate->getId()] = $row;
        }

        return $byCandidate;
    }

    /**
     * Combien de profils ce recruteur a déjà examinés dans chaque recherche, en UNE requête pour
     * tout le lot (task API-027).
     *
     * BORNÉ au jeu de résultats courant, d'où la jointure sur l'appartenance : le jeu rétrécit —
     * `SearchMembershipRecalculator` retire la ligne d'un profil qui cesse de correspondre — là où
     * la ligne de revue, elle, survit. Sans cette jointure, l'avancement pourrait dépasser le total
     * et l'écran afficherait plus de 100 %.
     *
     * Écarter n'annule pas d'avoir vu : seul `seenAt` est consulté, jamais `dismissedAt`.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, int> nombre de vus indexé par id interne de recherche
     */
    public function seenCountsFor(array $searches, Recruiter $recruiter): array
    {
        if ([] === $searches) {
            return [];
        }

        // Doctrine rend les agrégats en chaînes selon le driver : on caste, sans prétendre au type.
        /** @var list<array{searchId: int|string, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.search) AS searchId', 'COUNT(DISTINCT r.id) AS total')
            ->innerJoin(
                SearchResultMembership::class,
                'm',
                Join::WITH,
                'm.search = r.search AND m.candidate = r.candidate',
            )
            ->andWhere('r.search IN (:searches)')->setParameter('searches', $searches)
            ->andWhere('r.recruiter = :recruiter')->setParameter('recruiter', $recruiter)
            ->andWhere('r.seenAt IS NOT NULL')
            ->groupBy('r.search')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['searchId']] = (int) $row['total'];
        }

        return $counts;
    }

    public function seenCountFor(SavedSearch $search, Recruiter $recruiter): int
    {
        return $this->seenCountsFor([$search], $recruiter)[(int) $search->getId()] ?? 0;
    }
}
