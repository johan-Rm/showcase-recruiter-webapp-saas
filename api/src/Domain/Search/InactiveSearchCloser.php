<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Entity\SavedSearch;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Range les recherches que plus personne ne touche.
 *
 * Une recherche ne se supprime pas : elle se **clôt**, exactement comme si son auteur avait déclaré
 * le travail terminé. Elle garde son jeu, ses retenus et ses écartés, et se rouvre d'un geste.
 *
 * Pourquoi automatiser un geste qui existe déjà : personne ne le pose. Les recherches s'empilent —
 * cinquante-cinq ouvertes sur un seul compte de la base de démonstration — et l'écran ne distingue
 * plus celle qu'on travaille de celle qu'on a lancée il y a trois semaines. C'est aussi ce qui borne
 * ce qu'un préchargement du plan de travail aurait à transporter.
 *
 * **Par lots**, et c'est délibéré : la table entière n'a pas à tenir en mémoire pour fermer trois
 * recherches, et une base qui grandit ne doit pas transformer cette commande en risque.
 */
final readonly class InactiveSearchCloser
{
    /** Assez pour que la requête reste courte, assez peu pour que la mémoire ne monte pas. */
    private const int BATCH = 100;

    public function __construct(
        private EntityManagerInterface $em,
        private int $inactivityDays,
    ) {
    }

    /** Le seuil courant, en jours — ce que la commande annonce avant d'agir. */
    public function inactivityDays(): int
    {
        return $this->inactivityDays;
    }

    /**
     * Ce que la prochaine passe fermerait, sans rien fermer.
     *
     * Même requête, sans écriture : sur une base qui n'a jamais rangé, la première clôture peut en
     * emporter beaucoup, et cela se regarde avant de s'appliquer.
     *
     * @return list<array{uuid: string, name: string}>
     */
    public function pending(?DateTimeImmutable $now = null): array
    {
        /** @var list<SavedSearch> $searches */
        $searches = $this->inactiveQuery($now)->getQuery()->getResult();

        return array_map(
            static fn (SavedSearch $search): array => [
                'uuid' => $search->getUuid()->toString(),
                'name' => $search->name,
            ],
            $searches,
        );
    }

    /**
     * Ferme ce qui n'a pas été touché depuis le seuil, et rend ce qui a été fermé.
     *
     * `$now` est injectable : un test ne peut pas attendre quinze jours pour vérifier une clôture.
     *
     * Rend des VALEURS et non des entités : l'unité de travail est vidée entre deux lots, et une
     * entité détachée qu'on promènerait dans une commande finirait par surprendre.
     *
     * @return list<array{uuid: string, name: string}> ce que cette passe a rangé
     */
    public function close(?DateTimeImmutable $now = null): array
    {
        $closed = [];

        do {
            /** @var list<SavedSearch> $batch */
            $batch = $this->inactiveQuery($now)->setMaxResults(self::BATCH)->getQuery()->getResult();

            foreach ($batch as $search) {
                // La date de clôture est celle de la passe, pas celle du dernier contact : c'est le
                // moment où le produit a rangé, et c'est ce qu'un lecteur veut savoir.
                $search->closedAt = $now ?? new DateTimeImmutable();
                $closed[] = ['uuid' => $search->getUuid()->toString(), 'name' => $search->name];
            }

            $this->em->flush();
            // Les entités fermées ne servent plus : les garder attachées ferait grossir l'unité de
            // travail à chaque lot, et la commande finirait par manquer de mémoire sur une grosse
            // base — le défaut exact que l'import Jarvi a déjà rencontré.
            $this->em->clear();
        } while ([] !== $batch);

        return $closed;
    }

    /**
     * Les recherches ouvertes sans contact depuis le seuil, dans l'ordre où elles ont été créées.
     *
     * Une recherche DÉJÀ close est laissée intacte : la refermer déplacerait sa date de clôture, et
     * effacerait le moment où son auteur a vraiment arrêté.
     */
    private function inactiveQuery(?DateTimeImmutable $now): QueryBuilder
    {
        $limit = ($now ?? new DateTimeImmutable())->modify(\sprintf('-%d days', $this->inactivityDays));

        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(SavedSearch::class, 's')
            ->andWhere('s.closedAt IS NULL')
            ->andWhere('s.lastActivityAt < :limit')->setParameter('limit', $limit)
            ->orderBy('s.id', 'ASC');
    }
}
