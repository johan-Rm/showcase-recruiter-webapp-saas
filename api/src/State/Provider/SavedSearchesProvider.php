<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\SavedSearchOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SearchNewCountReader;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchProfileReviewRepository;
use App\Repository\SearchResultMembershipRepository;
use App\State\CollaborationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/searches : les recherches du recruteur courant + les recherches PARTAGÉES de son
 * organisation (auteur exposé). Chaque item porte le `newCount` du lecteur (task 072).
 *
 * @implements ProviderInterface<CollectionOutput<SavedSearchOutput>>
 */
final readonly class SavedSearchesProvider implements ProviderInterface
{
    public function __construct(
        private SavedSearchRepository $searches,
        private Security $security,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchNewCountReader $newCounts,
        private SearchResultMembershipRepository $memberships,
        private SearchProfileReviewRepository $reviews,
        private FavoriteRepository $favorites,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;
        if (!$recruiter instanceof Recruiter || $recruiter->organization->isRejected()) {
            throw new AccessDeniedHttpException();
        }

        $searches = $this->searches->findVisibleTo($recruiter);
        $counts = $this->newCounts->countsFor($searches, $recruiter);
        // Une requête groupée pour toutes les cartes : un COUNT par recherche ferait autant
        // d'allers-retours que de lignes affichées.
        $totals = $this->memberships->countsFor($searches);
        $seen = $this->reviews->seenCountsFor($searches, $recruiter);
        $retained = $this->favorites->countsFor($searches);

        $items = array_map(
            static fn (SavedSearch $search): SavedSearchOutput => CollaborationMapper::toSavedSearch(
                $search,
                $recruiter,
                $counts[(int) $search->getId()] ?? 0,
                $totals[(int) $search->getId()] ?? 0,
                $seen[(int) $search->getId()] ?? 0,
                $retained[(int) $search->getId()] ?? 0,
            ),
            $searches,
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
