<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\SearchWorkspaceEntryOutput;
use App\ApiResource\Output\SearchWorkspaceOutput;
use App\Domain\Search\ProfileFreshness;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Repository\FavoriteRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchProfileReviewRepository;
use App\Repository\SearchResultMembershipRepository;
use DateTimeImmutable;

/**
 * L'ÉTAT DE TRAVAIL D'UN RECRUTEUR — ses recherches ouvertes, en une réponse.
 *
 * Que des identifiants. Les fiches et les explications se demandent ailleurs, et à un autre rythme :
 * elles coûtent trente fois et quinze fois le prix d'un identifiant, alors qu'on n'en a besoin que de
 * ce qui est sur le point d'être affiché.
 *
 * **Cinq listes par recherche, chacune ENTIÈRE.** C'est tout ce que cette classe garantit, et c'est
 * ce qui permet à l'écran d'annoncer des nombres justes quel que soit ce qu'il a chargé : un compte
 * déduit des fiches en mémoire dirait « parmi ce que j'ai » et rétrécirait avec le chargement.
 *
 * **Trois lectures groupées, quel que soit le nombre de recherches.** C'est la seule difficulté de
 * cette route : une lecture par recherche transformerait un plan de travail en rafale d'appels.
 */
final readonly class SearchWorkspaceBuilder
{
    public function __construct(
        private SavedSearchRepository $searches,
        private SearchResultMembershipRepository $memberships,
        private FavoriteRepository $favorites,
        private SearchProfileReviewRepository $reviews,
    ) {
    }

    public function build(Recruiter $recruiter, ?DateTimeImmutable $at = null): SearchWorkspaceOutput
    {
        $now = $at ?? new DateTimeImmutable();

        $open = array_values(array_filter(
            $this->searches->findVisibleTo($recruiter),
            static fn (SavedSearch $search): bool => null === $search->closedAt,
        ));

        if ([] === $open) {
            return new SearchWorkspaceOutput([]);
        }

        $sets = $this->memberships->orderedCandidateUuidsForMany($open);
        $fresh = $this->memberships->newCandidateUuidsForMany(
            $open,
            $now->modify(\sprintf('-%d days', ProfileFreshness::NEW_FOR_DAYS)),
        );
        $retained = $this->favorites->candidateUuidsFor($open);
        $states = $this->reviews->statesForMany($open, $recruiter);

        $entries = [];
        foreach ($open as $search) {
            $searchId = (int) $search->getId();
            $resultIds = $sets[$searchId] ?? [];
            $dismissed = $states[$searchId]['dismissed'] ?? [];

            $entries[] = new SearchWorkspaceEntryOutput(
                $search->getUuid()->toString(),
                $resultIds,
                // Les retenus déduisent les écartés, comme partout : deux verdicts opposés, le
                // dernier posé l'emporte. Et ils suivent L'ORDRE DU JEU, celui qui peint l'onglet
                // des retenus — servis dans l'ordre où ils ont été posés, cet onglet changerait de
                // classement le jour où le client cesse de le demander à part.
                self::inSetOrder(array_diff($retained[$searchId] ?? [], $dismissed), $resultIds),
                $dismissed,
                $states[$searchId]['seen'] ?? [],
                // La nouveauté est un fait daté du PROFIL, partagé par toute l'organisation : elle
                // est lue, jamais déduite des fiches servies.
                self::inSetOrder($fresh[$searchId] ?? [], $resultIds),
            );
        }

        return new SearchWorkspaceOutput($entries);
    }

    /**
     * Une sous-liste rangée dans l'ordre du jeu.
     *
     * Le classement du jeu est celui du serveur — les mieux appariés d'abord. Une sous-liste qui
     * garderait l'ordre de sa propre table (l'ordre des mises en favori, par exemple) présenterait
     * les mêmes profils dans un autre ordre, et l'écran afficherait deux classements pour une même
     * recherche. Ce qui n'appartient pas au jeu ferme la marche.
     *
     * @param iterable<string> $subset
     * @param list<string>     $ordered
     *
     * @return list<string>
     */
    private static function inSetOrder(iterable $subset, array $ordered): array
    {
        $rank = array_flip($ordered);
        $wanted = [];
        foreach ($subset as $uuid) {
            $wanted[$uuid] = $rank[$uuid] ?? \PHP_INT_MAX;
        }

        asort($wanted);

        return array_keys($wanted);
    }
}
