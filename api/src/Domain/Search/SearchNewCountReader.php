<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Entity\SearchView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Badge `+N` : les profils ayant rejoint le jeu de résultats depuis la dernière consultation du
 * recruteur COURANT (task 072). Deux recruteurs sur une même recherche partagée ont donc des
 * compteurs indépendants — le repère est porté par SearchView, pas par la recherche.
 *
 * Ce compteur est le SEUL usage du repère de lecture, et il répond à « qu'est-ce qui a bougé depuis
 * que je suis passé ». Le badge « Nouveau » d'un profil, lui, dit son âge et ne consulte plus rien
 * d'ici (`ProfileFreshness`) : deux questions voisines, deux mécanismes, à ne pas refondre.
 */
final readonly class SearchNewCountReader
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param list<SavedSearch> $searches
     *
     * @return array<int, int> newCount indexé par id interne de recherche
     */
    public function countsFor(array $searches, Recruiter $recruiter): array
    {
        if ([] === $searches) {
            return [];
        }

        $markers = $this->markers($searches, $recruiter);

        // Un COUNT par recherche : le repère de lecture diffère d'une recherche à l'autre, il n'y a
        // pas de seuil commun à passer en une requête. Un recruteur a une poignée de recherches.
        $counts = [];
        foreach ($searches as $search) {
            $searchId = (int) $search->getId();
            $counts[$searchId] = $this->countSince($search, $markers[$searchId] ?? null);
        }

        return $counts;
    }

    public function countFor(SavedSearch $search, Recruiter $recruiter): int
    {
        return $this->countsFor([$search], $recruiter)[(int) $search->getId()] ?? 0;
    }

    /**
     * Ouvrir une recherche remet le repère du recruteur à jour — le badge retombe à 0.
     */
    public function markViewed(SavedSearch $search, Recruiter $recruiter): void
    {
        $view = $this->em->getRepository(SearchView::class)
            ->findOneBy(['search' => $search, 'recruiter' => $recruiter]);

        if ($view instanceof SearchView) {
            $view->lastViewedAt = new DateTimeImmutable();
        } else {
            $this->em->persist(new SearchView($search, $recruiter));
        }

        $this->em->flush();
    }

    /**
     * Jamais consulté (`null`) = tout le jeu de résultats est nouveau pour ce recruteur. C'est le
     * cas d'un membre qui découvre une recherche partagée.
     */
    private function countSince(SavedSearch $search, ?DateTimeImmutable $lastViewedAt): int
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(SearchResultMembership::class, 'm')
            ->andWhere('m.search = :search')->setParameter('search', $search);

        if (null !== $lastViewedAt) {
            $qb->andWhere('m.firstMatchedAt > :since')->setParameter('since', $lastViewedAt);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param list<SavedSearch> $searches
     *
     * @return array<int, DateTimeImmutable> repère de lecture indexé par id interne de recherche
     */
    private function markers(array $searches, Recruiter $recruiter): array
    {
        /** @var list<SearchView> $views */
        $views = $this->em->getRepository(SearchView::class)
            ->findBy(['search' => $searches, 'recruiter' => $recruiter]);

        $markers = [];
        foreach ($views as $view) {
            $markers[(int) $view->search->getId()] = $view->lastViewedAt;
        }

        return $markers;
    }
}
