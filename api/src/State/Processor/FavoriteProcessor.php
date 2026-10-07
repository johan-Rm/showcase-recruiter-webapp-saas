<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\FavoriteInput;
use App\ApiResource\Output\FavoriteOutput;
use App\Domain\Search\SearchActivityRecorder;
use App\Domain\Search\SearchListWriter;
use App\Entity\SavedSearch;
use App\Repository\CandidateRepository;
use App\Repository\SavedSearchRepository;
use App\Security\SavedSearchVoter;
use App\State\CollaborationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Favoris d'une recherche (task 073) : POST ajoute (idempotent sur (search, candidate)), DELETE
 * retire par candidateId. Écriture réservée à l'AUTEUR de la recherche.
 *
 * Un profil MASQUÉ est un favori valide — c'est le filet large de la Table.
 *
 * @implements ProcessorInterface<FavoriteInput|null, FavoriteOutput|null>
 */
final readonly class FavoriteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private SavedSearchRepository $searches,
        private CandidateRepository $candidates,
        private SearchListWriter $lists,
        private SearchActivityRecorder $activity,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?FavoriteOutput
    {
        $search = $this->writableSearch($uriVariables['searchId'] ?? null);

        // DELETE : /searches/{searchId}/favorites/{candidateId}
        if (isset($uriVariables['candidateId']) && \is_string($uriVariables['candidateId'])) {
            $candidate = $this->candidates->findOneByUuid($uriVariables['candidateId']);
            if (null === $candidate) {
                throw new NotFoundHttpException();
            }
            $this->lists->removeFavorite($search, $candidate);
            $this->activity->record($search);

            return null; // 204
        }

        // POST : { candidateId }
        $candidateId = $data instanceof FavoriteInput ? $data->candidateId : '';
        $candidate = $this->candidates->findOneByUuid($candidateId);
        if (null === $candidate || !$this->lists->isAddable($candidate)) {
            throw new NotFoundHttpException('Candidat introuvable.');
        }

        $favorite = $this->lists->addFavorite($search, $candidate, $search->owner);
        // Retenir ou libérer, c'est travailler la recherche : les deux repoussent sa clôture.
        $this->activity->record($search);

        return CollaborationMapper::toFavorite($favorite);
    }

    /**
     * Une recherche illisible renvoie 404 (son existence ne se déduit pas) ; une recherche lisible
     * mais partagée par un collègue renvoie 403 (elle est en lecture seule).
     */
    private function writableSearch(mixed $searchId): SavedSearch
    {
        $search = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;
        if (!$search instanceof SavedSearch || !$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(SavedSearchVoter::WRITE, $search)) {
            throw new AccessDeniedHttpException('Recherche partagée en lecture seule.');
        }

        return $search;
    }
}
