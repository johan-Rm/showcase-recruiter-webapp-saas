<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Stringable;

/**
 * Jeu de résultats MATÉRIALISÉ d'une recherche (task 072). C'est la source de vérité de
 * « les résultats de la recherche N » : recalculé à chaque changement de critères et à chaque
 * ingestion, il porte aussi la date d'entrée de chaque candidat.
 *
 * @extends ServiceEntityRepository<SearchResultMembership>
 */
class SearchResultMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchResultMembership::class);
    }

    /**
     * Une page du jeu de résultats, **profils vérifiés en tête**.
     *
     * Ce tri est serveur et non client : trié dans le navigateur, il ne rangerait que la page
     * courante — les vérifiés de la page 2 resteraient derrière les autres de la page 1. C'est la
     * seule hiérarchie de la Table, et elle incarne la promesse (rencontré par showcase-recruiter-webapp-saas).
     *
     * `firstMatchedAt DESC` ensuite : à qualification égale, les entrées récentes d'abord. Puis
     * l'id, pour que deux appels sur la même page renvoient le même ordre.
     *
     * @return array{0: list<SearchResultMembership>, 1: int} la page et le total du jeu
     */
    public function page(SavedSearch $search, int $page, int $perPage, ?ProfileScope $scope = null): array
    {
        $scope ??= ProfileScope::all();

        $counter = $scope->apply(
            $this->createQueryBuilder('m')->select('COUNT(m.id)')->innerJoin('m.candidate', 'c')
                ->andWhere('m.search = :search')->setParameter('search', $search),
        );
        $total = (int) $counter->getQuery()->getSingleScalarResult();

        $qb = $scope->apply(
            $this->createQueryBuilder('m')
                ->addSelect('c', 'p', 'j')
                ->innerJoin('m.candidate', 'c')
                ->innerJoin('c.person', 'p')
                ->leftJoin('c.jarvi', 'j')
                ->andWhere('m.search = :search')->setParameter('search', $search)
                ->setFirstResult(($page - 1) * $perPage)
                ->setMaxResults($perPage),
        );

        /** @var list<SearchResultMembership> $rows */
        $rows = self::ordered($qb)->getQuery()->getResult();

        return [$rows, $total];
    }

    /**
     * TOUS les identifiants publics du jeu, dans le MÊME ordre que `page()`.
     *
     * Le client connaît ainsi le jeu entier — sa taille, son ordre, ce qu'il contient — sans avoir
     * téléchargé une seule carte, ce qui lui permet de compter, de retenir une sélection qui survit
     * au filtrage, et de charger les cartes manquantes ensuite. Des uuid et non des cartes : la liste
     * complète d'une recherche à 5 000 profils pèse alors quelques dizaines de Ko.
     *
     * L'ordre identique n'est pas un confort : sans lui, la page 2 du client ne serait pas la page 2
     * du serveur, et un index dans cette liste ne désignerait pas la même personne des deux côtés.
     *
     * `$limit` borne la liste au même rang que ce que l'API sert (cf. `SearchResultsBuilder`) : la
     * couper en SQL plutôt qu'en PHP évite de faire remonter les milliers d'identifiants d'une
     * recherche trop large pour n'en garder que cent.
     *
     * @return list<string>
     */
    public function orderedCandidateUuidsFor(SavedSearch $search, ?ProfileScope $scope = null, ?int $limit = null): array
    {
        $qb = ($scope ?? ProfileScope::all())->apply(
            $this->createQueryBuilder('m')
                ->select('c.uuid AS uuid')
                ->innerJoin('m.candidate', 'c')
                ->andWhere('m.search = :search')->setParameter('search', $search)
                ->setMaxResults($limit),
        );

        /** @var list<array{uuid: Stringable|string}> $rows */
        $rows = self::ordered($qb)->getQuery()->getResult();

        return array_map(static fn (array $row): string => (string) $row['uuid'], $rows);
    }

    /**
     * Les identifiants publics de PLUSIEURS jeux, dans l'ordre de chacun.
     *
     * Une requête pour toutes les recherches, et non une par recherche : le plan de travail d'un
     * recruteur en compte des dizaines, et autant d'allers-retours transformeraient une lecture en
     * rafale. C'est la seule raison d'être de cette méthode — le résultat est celui
     * d'`orderedCandidateUuidsFor` appelée en boucle.
     *
     * La borne s'applique **par recherche** et se pose en PHP : un `LIMIT` global couperait la
     * dernière au milieu, et une fenêtre SQL par partition dupliquerait l'ordre du jeu, que ce
     * dépôt tient volontairement en un seul endroit.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, list<string>> identifiants publics, indexés par id interne de recherche
     */
    public function orderedCandidateUuidsForMany(array $searches, ?int $limitPerSearch = null): array
    {
        if ([] === $searches) {
            return [];
        }

        $qb = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.search) AS searchId', 'c.uuid AS uuid')
            ->innerJoin('m.candidate', 'c')
            ->andWhere('m.search IN (:searches)')->setParameter('searches', $searches)
            // La recherche d'abord, l'ordre du jeu ensuite : `ordered()` n'ajoute que ce second.
            ->orderBy('searchId', 'ASC');

        /** @var list<array{searchId: int|string, uuid: Stringable|string}> $rows */
        $rows = self::ordered($qb)->getQuery()->getResult();

        $bySearch = [];
        foreach ($rows as $row) {
            $searchId = (int) $row['searchId'];
            if (null !== $limitPerSearch && \count($bySearch[$searchId] ?? []) >= $limitPerSearch) {
                continue;
            }

            $bySearch[$searchId][] = (string) $row['uuid'];
        }

        return $bySearch;
    }

    /**
     * Les profils NOUVEAUX de plusieurs recherches — ceux dont l'arrivée est récente.
     *
     * La nouveauté est un fait daté qui appartient au PROFIL : il vient d'arriver, et il s'éteint en
     * vieillissant. Elle est donc la même pour tous les membres de l'organisation, contrairement au
     * vu et à l'écarté qui sont personnels.
     *
     * Lue ici plutôt que déduite des fiches servies : déduite, elle vaudrait « les nouveaux parmi ce
     * qui est chargé », c'est-à-dire un compte qui rétrécit avec le chargement.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, list<string>> identifiants publics, indexés par id interne de recherche
     */
    public function newCandidateUuidsForMany(array $searches, DateTimeImmutable $since): array
    {
        if ([] === $searches) {
            return [];
        }

        /** @var list<array{searchId: int|string, uuid: Stringable|string}> $rows */
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.search) AS searchId', 'c.uuid AS uuid')
            ->innerJoin('m.candidate', 'c')
            ->andWhere('m.search IN (:searches)')->setParameter('searches', $searches)
            ->andWhere('c.createdAt > :since')->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        $bySearch = [];
        foreach ($rows as $row) {
            $bySearch[(int) $row['searchId']][] = (string) $row['uuid'];
        }

        return $bySearch;
    }

    /**
     * L'ordre du jeu de résultats — **les mieux appariés d'abord**.
     *
     * Ce tri est serveur et non client : trié dans le navigateur, il ne rangerait que la page
     * courante — les mieux appariés de la page 2 resteraient derrière les moins bons de la page 1.
     * Sur un jeu qu'on parcourt profil par profil, l'ordre EST la fonctionnalité.
     *
     * Le rang vient de `matchedCount`, matérialisé avec l'appartenance : depuis que les mots-clés se
     * relient en OU, un profil peut entrer en ne satisfaisant qu'un critère sur quatre, et un jeu
     * sans hiérarchie mêlerait celui-là à ceux qui les satisfont tous.
     *
     * **Les profils rencontrés passent en départage, plus en tête.** C'était la seule hiérarchie du
     * jeu et elle incarnait la promesse (rencontré par showcase-recruiter-webapp-saas) ; elle cède à ce que le recruteur a
     * demandé, mais elle garde le dernier mot À CORRESPONDANCE ÉGALE — là où elle ne contredit rien.
     *
     * `firstMatchedAt DESC` ensuite : à qualification égale, les entrées récentes d'abord. Puis
     * l'id, pour que deux appels sur la même page renvoient le même ordre.
     *
     * Un seul endroit : la page et la liste d'identifiants doivent trier PAREIL, et deux copies de
     * ce `ORDER BY` divergeraient au premier ajustement.
     *
     * Suppose l'alias `c` joint sur le candidat.
     */
    private static function ordered(QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->addOrderBy('m.matchedCount', 'DESC')
            ->addOrderBy('CASE WHEN c.starInterviewedAt IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('m.firstMatchedAt', 'DESC')
            ->addOrderBy('m.id', 'ASC');
    }

    /**
     * Ids internes des candidats du jeu de résultats, indexés pour le test d'appartenance.
     *
     * Sert à vérifier qu'une sélection reçue du client appartient bien à la recherche : sans ce
     * contrôle, poster des identifiants arbitraires suffirait à sortir n'importe quel profil.
     *
     * @return array<int, true>
     */
    public function candidateIdsFor(SavedSearch $search): array
    {
        /** @var list<array{candidateId: int|string}> $rows */
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.candidate) AS candidateId')
            ->andWhere('m.search = :search')->setParameter('search', $search)
            ->getQuery()
            ->getResult();

        return array_fill_keys(
            array_map(static fn (array $row): int => (int) $row['candidateId'], $rows),
            true,
        );
    }

    /**
     * Taille du jeu de résultats de chaque recherche, en UNE requête groupée : la collection en
     * affiche une par carte, un COUNT par recherche ferait autant d'allers-retours que de cartes.
     *
     * Une recherche sans aucune ligne est absente du résultat — l'appelant retombe sur 0.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, int> total indexé par id interne de recherche
     */
    public function countsFor(array $searches): array
    {
        if ([] === $searches) {
            return [];
        }

        // Doctrine rend les agrégats en chaînes selon le driver : on caste, sans prétendre au type.
        /** @var list<array{searchId: int|string, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.search) AS searchId', 'COUNT(m.id) AS total')
            ->andWhere('m.search IN (:searches)')->setParameter('searches', $searches)
            ->groupBy('m.search')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['searchId']] = (int) $row['total'];
        }

        return $counts;
    }
}
