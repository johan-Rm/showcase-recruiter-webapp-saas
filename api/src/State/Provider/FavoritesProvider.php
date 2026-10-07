<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\FavoriteOutput;
use App\Entity\Favorite;
use App\Entity\SavedSearch;
use App\Repository\SavedSearchRepository;
use App\Security\SavedSearchVoter;
use App\State\CollaborationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/searches/{searchId}/favorites (task 073).
 *
 * Lecture ouverte à qui peut lire la recherche : le destinataire d'une recherche partagée voit les
 * favoris de l'auteur, sans pouvoir y toucher.
 *
 * @implements ProviderInterface<CollectionOutput<FavoriteOutput>>
 */
final readonly class FavoritesProvider implements ProviderInterface
{
    public function __construct(
        private SavedSearchRepository $searches,
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $searchId = $uriVariables['searchId'] ?? null;
        $search = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;
        if (!$search instanceof SavedSearch || !$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }

        $favorites = $this->em->getRepository(Favorite::class)
            ->findBy(['search' => $search], ['createdAt' => 'DESC', 'id' => 'DESC']);

        $items = array_map(
            static fn (Favorite $favorite): FavoriteOutput => CollaborationMapper::toFavorite($favorite),
            $favorites,
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
