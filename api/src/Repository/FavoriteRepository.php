<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\SavedSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Stringable;

/**
 * Les profils retenus dans une recherche (task API-027).
 *
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * Combien de profils retenus par recherche, en UNE requête pour tout le lot.
     *
     * Aucun filtre sur l'appartenance au jeu de résultats, et c'est délibéré : `FavoritesProvider`
     * sert tous les favoris d'une recherche sans en écarter aucun, et `SavedSearchOutput` pose la
     * règle — la carte annonce ce que la Table servira. Un profil retenu puis sorti du jeu reste
     * donc compté, sinon la carte afficherait 5 là où la Table montre 8.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, int> nombre de retenus indexé par id interne de recherche
     */
    public function countsFor(array $searches): array
    {
        if ([] === $searches) {
            return [];
        }

        // Doctrine rend les agrégats en chaînes selon le driver : on caste, sans prétendre au type.
        /** @var list<array{searchId: int|string, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.search) AS searchId', 'COUNT(f.id) AS total')
            ->andWhere('f.search IN (:searches)')->setParameter('searches', $searches)
            ->groupBy('f.search')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['searchId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Les profils retenus de PLUSIEURS recherches, en identifiants publics.
     *
     * Le compteur ne suffit pas au plan de travail : l'étoile a besoin de savoir LESQUELS. Une
     * requête pour toutes les recherches, même raison que le comptage groupé ci-dessus.
     *
     * @param list<SavedSearch> $searches
     *
     * @return array<int, list<string>> identifiants publics, indexés par id interne de recherche
     */
    public function candidateUuidsFor(array $searches): array
    {
        if ([] === $searches) {
            return [];
        }

        /** @var list<array{searchId: int|string, uuid: Stringable|string}> $rows */
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.search) AS searchId', 'c.uuid AS uuid')
            ->innerJoin('f.candidate', 'c')
            ->andWhere('f.search IN (:searches)')->setParameter('searches', $searches)
            ->getQuery()
            ->getResult();

        $bySearch = [];
        foreach ($rows as $row) {
            $bySearch[(int) $row['searchId']][] = (string) $row['uuid'];
        }

        return $bySearch;
    }

    public function countFor(SavedSearch $search): int
    {
        return $this->countsFor([$search])[(int) $search->getId()] ?? 0;
    }
}
