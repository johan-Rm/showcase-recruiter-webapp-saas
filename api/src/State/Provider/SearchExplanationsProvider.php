<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\SearchExplanationOutput;
use App\ApiResource\Output\SearchExplanationsOutput;
use App\Domain\Search\CriterionMatcher;
use App\Entity\Candidate;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchResultMembershipRepository;
use App\Security\SavedSearchVoter;
use App\State\CandidateMapper;
use App\State\SearchResultsBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/searches/{searchId}/explanations : pourquoi ces profils-là, page par page.
 *
 * Rien n'est stocké : l'explication se recalcule depuis les critères de la recherche et le profil.
 * La garder en base créerait une donnée à invalider chaque fois qu'un critère change ou qu'un profil
 * est réimporté — deux moments fréquents, et deux occasions de servir une explication périmée.
 *
 * @implements ProviderInterface<SearchExplanationsOutput>
 */
final readonly class SearchExplanationsProvider implements ProviderInterface
{
    public function __construct(
        private SavedSearchRepository $searches,
        private SearchResultMembershipRepository $memberships,
        private CriterionMatcher $matcher,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SearchExplanationsOutput
    {
        $searchId = $uriVariables['searchId'] ?? null;
        $search = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;

        // LA MÊME GARDE QUE LA RECHERCHE, et le même 404 : qui ne peut pas la voir ne doit pas
        // pouvoir distinguer « elle n'existe pas » de « elle ne vous regarde pas ».
        if (!$search instanceof SavedSearch || !$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }

        $request = $this->requestStack->getCurrentRequest() ?? new Request();
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = SearchResultsBuilder::clampPerPage(
            $request->query->getInt('perPage', SearchResultsBuilder::DEFAULT_PER_PAGE),
        );

        // AUCUN PÉRIMÈTRE : la page suit le jeu ENTIER, dans l'ordre que sert la route d'état.
        // Retirer les écartés ici décalerait les pages par rapport à cet ordre, et l'appelant ne
        // saurait plus à quels profils sa page répond.
        [$rows, $total] = $this->memberships->page($search, $page, $perPage);

        return new SearchExplanationsOutput(
            $this->explain($rows, $search),
            $total,
            $page,
            $perPage,
        );
    }

    /**
     * Les explications de la page, profils inexpliqués écartés.
     *
     * **Une entrée absente se lit « aucun critère satisfait »**, ce qui est vrai — un profil peut
     * appartenir au jeu par un critère que l'explication ne sait pas énoncer. Servir une entrée vide
     * dirait la même chose en plus lourd.
     *
     * @param list<SearchResultMembership> $rows
     *
     * @return list<SearchExplanationOutput>
     */
    private function explain(array $rows, SavedSearch $search): array
    {
        // LE COÛT EST GROUPÉ, et ce n'est pas une optimisation de confort : expliquer un mot-clé lit
        // les postes écrits du profil, et sans ce préchargement chaque profil de la page
        // déclencherait sa propre requête au moment de s'expliquer.
        $this->matcher->warmUp(array_map(
            static fn (SearchResultMembership $row): Candidate => $row->candidate,
            $rows,
        ));

        $explained = [];

        foreach ($rows as $row) {
            $matches = $this->matcher->match($row->candidate, $search->criteria);

            if ([] === $matches) {
                continue;
            }

            $explained[] = new SearchExplanationOutput(
                (string) $row->candidate->getUuid(),
                array_map(CandidateMapper::toCriterionMatch(...), $matches),
            );
        }

        return $explained;
    }
}
