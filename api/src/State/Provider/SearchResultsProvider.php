<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CandidateCardOutput;
use App\ApiResource\Output\SearchResultsOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SearchActivityRecorder;
use App\Domain\Search\SearchNewCountReader;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Enum\SearchScope;
use App\Repository\SavedSearchRepository;
use App\Security\SavedSearchVoter;
use App\State\SearchResultsBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/searches/{searchId}/results — le jeu de résultats D'UNE RECHERCHE.
 *
 * Distinct de /search/candidates, qui interroge le moteur avec des critères passés en query et ne
 * connaît aucune recherche. Ici le jeu est celui que l'API a MATÉRIALISÉ (search_result_membership,
 * recalculé à chaque changement de critères et à chaque ingestion) : le front n'a plus à rejouer les
 * critères pour reconstituer un jeu que l'API connaît déjà — deux vérités pour le même jeu
 * divergeaient dès qu'un profil devenait matchant entre deux recalculs.
 *
 * Chaque carte porte donc son ÉTAT DE TRAVAIL dans la recherche — nouveau, favori — là où
 * il fallait sinon trois appels et autant d'intersections côté client. La réponse porte en plus la
 * liste COMPLÈTE des identifiants du jeu, pagination comprise (cf. SearchResultsOutput).
 *
 * Lecture ouverte à qui peut lire la recherche : le destinataire d'un partage voit le même jeu, les
 * mêmes favoris que l'auteur. Ses nouveautés, elles, restent les siennes.
 *
 * @implements ProviderInterface<SearchResultsOutput<CandidateCardOutput>>
 */
final readonly class SearchResultsProvider implements ProviderInterface
{
    public function __construct(
        private SavedSearchRepository $searches,
        private SearchNewCountReader $newCounts,
        private SearchActivityRecorder $activity,
        private Security $security,
        private RequestStack $requestStack,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchResultsBuilder $builder,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SearchResultsOutput
    {
        $searchId = $uriVariables['searchId'] ?? null;
        $search = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;
        if (!$search instanceof SavedSearch || !$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }

        $recruiter = $this->currentRecruiter();
        $request = $this->requestStack->getCurrentRequest() ?? new Request();

        $results = $this->builder->build(
            $search,
            $recruiter,
            max(1, $request->query->getInt('page', 1)),
            SearchResultsBuilder::clampPerPage(
                $request->query->getInt('perPage', SearchResultsBuilder::DEFAULT_PER_PAGE),
            ),
            // Par défaut on sert la PILE DE TRAVAIL : les profils écartés en sont sortis. `?scope`
            // ouvre les deux autres points de vue — la corbeille, d'où l'on remet, et la récolte,
            // dont la page seule ne suffisait pas à connaître le contenu au-delà des trente premiers.
            SearchScope::fromRequest($request->query->get('scope')),
        );

        // Le jeu vient d'être servi, donc vu : le badge de la recherche retombe à 0 aux appels
        // suivants. C'est ICI et non sur la fiche de la recherche — ouvrir une recherche, c'est
        // regarder ses résultats. Après la construction, qui a besoin du repère d'AVANT.
        $this->newCounts->markViewed($search, $recruiter);
        // Ouvrir une recherche suffit à la garder vivante : ce qu'on regarde encore ne se ferme pas
        // dans le dos de son auteur.
        $this->activity->record($search);

        return $results;
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
