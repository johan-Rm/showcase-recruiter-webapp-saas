<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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
use App\Security\SavedSearchVoter;
use App\State\CollaborationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/searches/{id} — la fiche d'une recherche : son nom, ses critères, son auteur (task 072).
 *
 * Lecture PURE : elle ne touche pas au repère de lecture. C'est
 * `GET /searches/{searchId}/results` qui le remet à jour, parce qu'ouvrir une recherche c'est
 * regarder ses résultats — lire ses métadonnées n'est pas les avoir vus, et remettre le badge à zéro
 * ici empêcherait le jeu de résultats de dire QUELS profils sont nouveaux.
 *
 * Une recherche qu'on ne peut pas lire renvoie 404, pas 403 : son existence même ne se déduit pas.
 *
 * @implements ProviderInterface<SavedSearchOutput>
 */
final readonly class SavedSearchProvider implements ProviderInterface
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

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SavedSearchOutput
    {
        $searchId = $uriVariables['id'] ?? null;
        $search = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;
        if (!$search instanceof SavedSearch) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }

        $recruiter = $this->currentRecruiter();

        return CollaborationMapper::toSavedSearch(
            $search,
            $recruiter,
            $this->newCounts->countFor($search, $recruiter),
            $this->memberships->count(['search' => $search]),
            $this->reviews->seenCountFor($search, $recruiter),
            $this->favorites->countFor($search),
        );
    }

    private function currentRecruiter(): Recruiter
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;
        if (!$recruiter instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        return $recruiter;
    }
}
