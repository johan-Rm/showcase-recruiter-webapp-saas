<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CandidateCardOutput;
use App\ApiResource\Output\SearchProfilesOutput;
use App\Domain\Candidate\RevealedAccess;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Candidate;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Repository\CandidateRepository;
use App\Repository\RevealedProfileRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchResultMembershipRepository;
use App\State\CandidateMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/searches/profiles : les premières fiches de plusieurs recherches, dédoublonnées.
 *
 * L'appelant nomme des recherches et dit combien de profils il veut de chacune. Le serveur choisit
 * lesquels — ce sont les premiers de l'ordre du jeu, celui-là même que sert `/searches/workspace` —,
 * les dédoublonne, et rend les fiches.
 *
 * @implements ProviderInterface<SearchProfilesOutput>
 */
final readonly class SearchProfilesProvider implements ProviderInterface
{
    /** Ce qu'on prend de chaque recherche quand l'appelant ne le dit pas. */
    public const int DEFAULT_TAKE = 30;

    /**
     * Bornes hautes, et elles RÉDUISENT au lieu de refuser.
     *
     * Une demande trop large est une maladresse d'appelant, pas une faute : la servir amputée vaut
     * mieux qu'une erreur qu'il faudrait traiter. Les deux bornes se lisent ensemble — cinquante
     * recherches de cent profils font au pire cinq mille fiches, ce qu'aucun écran ne demande mais
     * qu'un appel maladroit pourrait réclamer.
     */
    public const int MAX_TAKE = 100;
    public const int MAX_SEARCHES = 50;

    public function __construct(
        private Security $security,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SavedSearchRepository $searches,
        private SearchResultMembershipRepository $memberships,
        private CandidateRepository $candidates,
        private RevealedProfileRepository $revealed,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SearchProfilesOutput
    {
        $recruiter = $this->currentRecruiter();
        $request = $this->requestStack->getCurrentRequest();

        $wanted = self::requestedIds($request?->query->get('ids') ?? '');
        $take = min(self::MAX_TAKE, max(1, $request?->query->getInt('take', self::DEFAULT_TAKE) ?? self::DEFAULT_TAKE));

        if ([] === $wanted) {
            return new SearchProfilesOutput([]);
        }

        // Le périmètre du lecteur d'abord : une recherche qu'il ne peut pas voir n'entre pas dans la
        // liste, et rien ne distingue dans la réponse « elle n'existe pas » de « elle ne vous
        // regarde pas » — c'est cette indistinction qui empêche de deviner ce qui existe.
        $visible = array_values(array_filter(
            $this->searches->findVisibleTo($recruiter),
            static fn (SavedSearch $search): bool => \in_array($search->getUuid()->toString(), $wanted, true),
        ));

        if ([] === $visible) {
            return new SearchProfilesOutput([]);
        }

        $sets = $this->memberships->orderedCandidateUuidsForMany($visible, $take);

        // LES PROFILS UNIQUES, et eux seuls : c'est toute la raison d'être du lot. `array_unique`
        // garde la première occurrence, donc l'ordre de la première recherche qui ramène un profil.
        $uuids = array_values(array_unique(array_merge(...array_values($sets))));
        $pool = $this->candidates->findByUuids($uuids);
        $access = $this->revealed->revealedTypesByCandidate($recruiter->organization);

        return new SearchProfilesOutput(array_map(
            static fn (Candidate $candidate): CandidateCardOutput => CandidateMapper::toCard(
                $candidate,
                RevealedAccess::fromTypes($access[(int) $candidate->getId()] ?? []),
                // Aucun état de travail : ces fiches n'appartiennent à aucune recherche en
                // particulier, et le même profil peut être retenu ici et écarté là. L'appartenance
                // vit dans les listes que sert `/searches/workspace`.
                null,
                null,
                null,
                null,
                null,
            ),
            $pool,
        ));
    }

    /**
     * Les identifiants demandés, dédoublonnés et bornés.
     *
     * Triés : deux demandes du même lot doivent avoir la même adresse, sans quoi rien ne permet de
     * reconnaître un appel déjà en vol — ni au client, ni à un intermédiaire.
     *
     * @return list<string>
     */
    private static function requestedIds(string $raw): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', $raw)),
            static fn (string $id): bool => '' !== $id,
        )));
        sort($ids);

        return \array_slice($ids, 0, self::MAX_SEARCHES);
    }

    private function currentRecruiter(): Recruiter
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;

        if (!$recruiter instanceof Recruiter || $recruiter->organization->isRejected()) {
            throw new AccessDeniedHttpException();
        }

        return $recruiter;
    }
}
