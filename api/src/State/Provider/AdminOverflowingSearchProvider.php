<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminOverflowingSearchOutput;
use App\ApiResource\Output\CollectionOutput;
use App\Entity\SavedSearch;
use App\Repository\SavedSearchRepository;
use App\State\SearchResultsBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/searches : les recherches dont le vivier ramène plus que ce qu'on sert.
 *
 * Le seuil n'est pas un réglage de cet écran : c'est **la borne de chargement elle-même**. Un
 * inventaire qui aurait son propre seuil finirait par lister des recherches que le recruteur voit
 * entièrement, ou par en taire qui débordent.
 *
 * @implements ProviderInterface<CollectionOutput<AdminOverflowingSearchOutput>>
 */
final readonly class AdminOverflowingSearchProvider implements ProviderInterface
{
    private const int PER_PAGE_DEFAULT = 20;
    private const int PER_PAGE_MAX = 100;

    public function __construct(
        private SavedSearchRepository $searches,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $request = $this->requestStack->getCurrentRequest();
        $page = max(1, $request?->query->getInt('page', 1) ?? 1);
        $perPage = min(
            self::PER_PAGE_MAX,
            max(1, $request?->query->getInt('perPage', self::PER_PAGE_DEFAULT) ?? self::PER_PAGE_DEFAULT),
        );

        $threshold = SearchResultsBuilder::RECOMMENDED_LIMIT;

        $items = array_map(
            static function (array $row) use ($threshold): AdminOverflowingSearchOutput {
                /** @var SavedSearch $search */
                $search = $row['search'];
                $person = $search->owner->getPerson();

                return new AdminOverflowingSearchOutput(
                    $search->getUuid()->toString(),
                    $search->name,
                    $search->criteria,
                    $search->organization->name,
                    trim($person->givenName.' '.$person->familyName),
                    $row['matchCount'],
                    $threshold,
                    null !== $search->closedAt,
                    $search->lastActivityAt->format('c'),
                );
            },
            $this->searches->findOverflowing($threshold, $page, $perPage),
        );

        return new CollectionOutput($items, $this->searches->countOverflowing($threshold), $page, $perPage);
    }
}
