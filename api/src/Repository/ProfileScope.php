<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

/**
 * Quelle part du jeu de résultats on regarde : la pile de travail, la corbeille, ou la récolte.
 *
 * Écarter fait **quitter la pile** — c'est ce qui rend un tri de plusieurs centaines de profils fini
 * plutôt qu'infini. Mais l'écart est réversible, donc les écartés doivent rester atteignables : des
 * périmètres, pas une suppression.
 *
 * DEUX LISTES et non une : une restriction (« seulement ceux-là ») et une soustraction (« sauf
 * ceux-là »), qui se cumulent. Les retenus se demandent en effet écartés déduits — sans quoi
 * « Favoris » cesserait d'être un sous-ensemble de la pile, et deux onglets de la même sélection
 * annonceraient des comptes qui ne se recouvrent pas.
 *
 * Porté par un objet et non par des paramètres nus : la page et la liste complète d'identifiants
 * doivent regarder EXACTEMENT le même sous-ensemble, sans quoi le total et l'ordre se contrediraient.
 * Un seul objet passé aux deux le garantit.
 */
final readonly class ProfileScope
{
    /**
     * @param list<int>|null $include ids internes auxquels se restreindre ; `null` = aucune restriction
     * @param list<int>      $exclude ids internes à retirer du résultat
     */
    private function __construct(
        private ?array $include,
        private array $exclude,
    ) {
    }

    /** Tout le jeu, sans distinction — le moteur hors recherche, ou l'export. */
    public static function all(): self
    {
        return new self(null, []);
    }

    /**
     * La pile de travail : tout sauf ce que ce recruteur a écarté.
     *
     * @param list<int> $dismissedIds
     */
    public static function excluding(array $dismissedIds): self
    {
        return new self(null, $dismissedIds);
    }

    /**
     * Seulement les écartés — la corbeille consultable, d'où l'on peut remettre.
     *
     * @param list<int> $dismissedIds
     */
    public static function only(array $dismissedIds): self
    {
        return new self($dismissedIds, []);
    }

    /**
     * Les retenus, écartés déduits.
     *
     * @param list<int> $favoriteIds
     * @param list<int> $dismissedIds
     */
    public static function retained(array $favoriteIds, array $dismissedIds): self
    {
        return new self($favoriteIds, $dismissedIds);
    }

    /** Suppose l'alias `c` joint sur le candidat. */
    public function apply(QueryBuilder $qb): QueryBuilder
    {
        if ([] === $this->include) {
            // Une restriction VIDE ne rend rien : aucun profil retenu, aucun écarté. Sans cette
            // garde, un `IN ()` invalide serait produit — ou pire, la restriction s'évanouirait et
            // le jeu entier ressortirait sous un périmètre qui devait être vide.
            return $qb->andWhere('1 = 0');
        }

        if (null !== $this->include) {
            $qb->andWhere('c.id IN (:includedCandidates)')
                ->setParameter('includedCandidates', $this->include);
        }

        if ([] !== $this->exclude) {
            $qb->andWhere('c.id NOT IN (:excludedCandidates)')
                ->setParameter('excludedCandidates', $this->exclude);
        }

        return $qb;
    }
}
