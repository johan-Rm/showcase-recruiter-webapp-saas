<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<SavedSearch>
 */
final class SavedSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedSearch::class);
    }

    public function findOneByUuid(string $uuid): ?SavedSearch
    {
        if (!Uuid::isValid($uuid)) {
            return null;
        }

        return $this->findOneBy(['uuid' => Uuid::fromString($uuid)]);
    }

    /**
     * LES RECHERCHES QUI DÉBORDENT — celles dont le vivier ramène plus que ce qu'on donne à
     * travailler.
     *
     * Le recruteur ne voit que cent profils ; ce compte-ci est le vrai. L'écart dit deux choses à qui
     * l'administre : une recherche trop large, ou une demande que le vivier couvre largement — et
     * c'est la seule façon de le savoir, l'écran ne montrant jamais l'excédent.
     *
     * `HAVING` et non `WHERE` : on filtre sur un agrégat, pas sur une colonne. Les recherches
     * supprimées sont écartées par le filtre de suppression douce, comme partout.
     *
     * @return list<array{search: SavedSearch, matchCount: int}> du plus large au plus étroit
     */
    public function findOverflowing(int $threshold, int $page, int $perPage): array
    {
        /** @var list<array{0: SavedSearch, matchCount: int|string}> $rows */
        $rows = $this->overflowingQuery($threshold)
            ->select('s', 'COUNT(m.id) AS matchCount')
            ->orderBy('matchCount', 'DESC')
            // L'identifiant en départage : sans lui, deux recherches de même taille peuvent changer
            // de place d'une page à l'autre, et une ligne se voir deux fois ou jamais.
            ->addOrderBy('s.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $row): array => ['search' => $row[0], 'matchCount' => (int) $row['matchCount']],
            $rows,
        );
    }

    /** Combien de recherches débordent — le total de la collection, hors pagination. */
    public function countOverflowing(int $threshold): int
    {
        return \count(
            $this->overflowingQuery($threshold)->select('s.id')->getQuery()->getResult(),
        );
    }

    /**
     * La jointure et le seuil, partagés par la page et son total.
     *
     * Deux copies de ce `HAVING` divergeraient au premier ajustement, et le total cesserait de
     * décrire la liste qu'il compte.
     */
    private function overflowingQuery(int $threshold): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->innerJoin(SearchResultMembership::class, 'm', Join::WITH, 'm.search = s')
            ->groupBy('s.id')
            ->having('COUNT(m.id) > :threshold')
            ->setParameter('threshold', $threshold);
    }

    /**
     * Ce que le recruteur peut LIRE : les siennes, plus les recherches partagées de son
     * organisation. Une recherche non partagée reste invisible de ses collègues — contrairement
     * aux dévoilés et au solde de crédits, qui sont communs (task 072).
     *
     * @return list<SavedSearch>
     */
    public function findVisibleTo(Recruiter $recruiter): array
    {
        /** @var list<SavedSearch> $searches */
        $searches = $this->createQueryBuilder('s')
            ->innerJoin('s.owner', 'o')->addSelect('o')
            ->andWhere('s.organization = :organization')
            ->andWhere('s.owner = :owner OR s.isShared = true')
            ->setParameter('organization', $recruiter->organization)
            ->setParameter('owner', $recruiter)
            ->orderBy('s.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $searches;
    }
}
