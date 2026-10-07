<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\CandidateCardOutput;
use App\ApiResource\Output\SearchResultsOutput;
use App\Domain\Candidate\RevealedAccess;
use App\Domain\Search\CriterionMatcher;
use App\Domain\Search\ProfileFreshness;
use App\Entity\Candidate;
use App\Entity\Favorite;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Enum\SearchScope;
use App\Repository\ProfileScope;
use App\Repository\RevealedProfileRepository;
use App\Repository\SearchProfileReviewRepository;
use App\Repository\SearchResultMembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le jeu de résultats d'une recherche, prêt à servir — cartes de la page demandée, état de travail
 * de chacune, et la liste complète des identifiants.
 *
 * Extrait du provider parce qu'il a maintenant DEUX appelants : la lecture du jeu
 * (`GET /searches/{id}/results`) et l'écriture d'une recherche, qui renvoie désormais ce qu'elle
 * ramène. Deux copies de ce calcul auraient divergé au premier état de carte ajouté.
 *
 * Ne touche PAS au repère de lecture, et ne le lit plus non plus : la nouveauté d'un profil est son
 * ÂGE, pas ce que le recruteur a déjà ouvert. Le repère ne sert plus qu'au compteur de la fiche de
 * recherche, dont l'appelant reste maître.
 */
final readonly class SearchResultsBuilder
{
    /** Taille de page par défaut du jeu de résultats. */
    public const int DEFAULT_PER_PAGE = 30;

    /** Plafond de page : au-delà, ce n'est plus une table qu'on lit mais un export. */
    public const int MAX_PER_PAGE = 200;

    /**
     * AU-DELÀ DE CE NOMBRE, une recherche est dite large — et c'est un conseil, jamais une limite.
     *
     * Le jeu se sert entier, quelle que soit sa taille : une recherche de 352 profils se parcourt
     * jusqu'au dernier. Ce nombre ne borne donc rien. Il répond à une autre question : à partir de
     * quand prévenir le recruteur que son tri sera difficile à mener jusqu'au bout, et qu'un critère
     * de plus lui rendrait service.
     *
     * Deux lecteurs, et un seul nombre : l'écran, qui l'annonce au recruteur par la configuration
     * publique, et l'inventaire d'administration, qui liste les recherches qui le dépassent. Deux
     * seuils finiraient par signaler à l'un ce qu'ils taisent à l'autre.
     */
    public const int RECOMMENDED_LIMIT = 100;

    public function __construct(
        private SearchResultMembershipRepository $memberships,
        private RevealedProfileRepository $revealed,
        private EntityManagerInterface $em,
        private CriterionMatcher $matcher,
        private SearchProfileReviewRepository $reviews,
    ) {
    }

    /** @return SearchResultsOutput<CandidateCardOutput> */
    public function build(
        SavedSearch $search,
        Recruiter $recruiter,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        SearchScope $view = SearchScope::PILE,
        ?DateTimeImmutable $at = null,
    ): SearchResultsOutput {
        // L'horloge est celle du SERVEUR, et elle est injectable : un test ne peut pas attendre huit
        // jours pour vérifier qu'un profil a cessé d'être nouveau.
        $now = $at ?? new DateTimeImmutable();

        // L'avancement du recruteur : ce qu'il a vu, et ce qu'il a écarté. Les écartés QUITTENT la
        // pile — c'est ce qui rend un tri de plusieurs centaines de profils fini plutôt qu'infini —
        // sans disparaître : les deux autres points de vue les rouvrent ou les déduisent.
        $states = $this->reviews->statesFor($search, $recruiter);
        $dismissedIds = array_keys($states['dismissed']);

        $access = $this->revealed->revealedTypesByCandidate($recruiter->organization);
        $favoriteIds = $this->favoriteCandidateIds($search);

        $scope = match ($view) {
            SearchScope::PILE => ProfileScope::excluding($dismissedIds),
            SearchScope::DISMISSED => ProfileScope::only($dismissedIds),
            SearchScope::FAVORITES => ProfileScope::retained(array_keys($favoriteIds), $dismissedIds),
        };

        [$rows, $total] = $this->memberships->page($search, $page, $perPage, $scope);
        $matcher = $this->matcher;
        $criteria = $search->criteria;

        // Expliquer un mot-clé lit les postes écrits du profil : les charger ICI, pour la page
        // entière, sinon chaque carte déclenche sa propre requête au moment de s'expliquer.
        $matcher->warmUp(array_map(
            static fn (SearchResultMembership $row): Candidate => $row->candidate,
            $rows,
        ));

        $cards = array_map(
            static function (SearchResultMembership $row) use ($now, $access, $favoriteIds, $matcher, $criteria, $states): CandidateCardOutput {
                $candidateId = (int) $row->candidate->getId();

                return CandidateMapper::toCard(
                    $row->candidate,
                    RevealedAccess::fromTypes($access[$candidateId] ?? []),
                    ProfileFreshness::isNew($row->candidate->getCreatedAt(), $now),
                    isset($favoriteIds[$candidateId]),
                    // Pourquoi ce profil est là : c'est la recherche qui porte les critères, la
                    // carte ne peut pas les deviner.
                    $matcher->match($row->candidate, $criteria),
                    isset($states['seen'][$candidateId]),
                    isset($states['dismissed'][$candidateId]),
                );
            },
            $rows,
        );

        // OUVRIR UNE RECHERCHE, c'est demander la première page de sa pile — et c'est le seul moment
        // où l'état de travail voyage. Les périmètres, eux, sont demandés APRÈS : le client connaît
        // déjà cet état quand il les appelle, le lui répéter serait du poids pour rien.
        $firstPage = 1 === $page;
        $opening = $firstPage && SearchScope::PILE === $view;

        return new SearchResultsOutput(
            $cards,
            // Le total est celui du JEU ENTIER et non celui de la page : c'est lui qui dit au
            // recruteur combien sa recherche ramène, et à l'administration laquelle est large.
            $total,
            $page,
            $perPage,
            // La liste ENTIÈRE des identifiants, sans borne : elle porte l'ordre du jeu, et un client
            // qui déroule doit savoir où il va avant d'y arriver. Servie à la première page seulement
            // — la répéter à chaque page serait du poids pour une information qui ne change pas.
            $firstPage ? $this->memberships->orderedCandidateUuidsFor($search, $scope) : null,
            // Les retenus ÉCARTÉS DÉDUITS, aligné sur `?scope=favorites` et non sur l'ancienne route
            // des favoris : deux sources qui répondent à la même question finissent par diverger, et
            // celle-là avait déjà coûté un compteur faux (cf. ENT-015).
            $opening ? $this->uuidsOf($search, array_keys($favoriteIds), $dismissedIds) : null,
            $opening ? $this->uuidsOf($search, $dismissedIds) : null,
            $opening ? $this->uuidsOf($search, array_keys($states['seen'])) : null,
        );
    }

    /** Borne une taille de page reçue du client : jamais zéro, jamais au-delà du plafond. */
    public static function clampPerPage(int $perPage): int
    {
        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    /**
     * Des identifiants internes vers les identifiants publics, DANS L'ORDRE DU JEU.
     *
     * Le même ordre que `resultIds`, et par le même chemin : le client n'a alors rien à retrier, et
     * deux listes de la même réponse ne peuvent pas se contredire.
     *
     * Ces listes ne sont PAS bornées comme le jeu servi, et c'est délibéré : elles disent ce que le
     * recruteur a fait de sa recherche ENTIÈRE. Les couper à cent ferait mentir le compteur qu'elles
     * existent pour rendre juste. Elles restent petites — on retient et on écarte par dizaines, pas
     * par milliers.
     *
     * @param list<int> $ids
     * @param list<int> $exclude
     *
     * @return list<string>
     */
    private function uuidsOf(SavedSearch $search, array $ids, array $exclude = []): array
    {
        return [] === $ids
            ? []
            : $this->memberships->orderedCandidateUuidsFor($search, ProfileScope::retained($ids, $exclude));
    }

    /**
     * Ids internes des candidats retenus dans la recherche, indexés pour le test d'appartenance.
     *
     * Une seule requête pour toute la page, et non une par ligne affichée.
     *
     * @return array<int, true>
     */
    private function favoriteCandidateIds(SavedSearch $search): array
    {
        /** @var list<array{candidateId: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(f.candidate) AS candidateId')
            ->from(Favorite::class, 'f')
            ->andWhere('f.search = :search')->setParameter('search', $search)
            ->getQuery()
            ->getResult();

        return array_fill_keys(
            array_map(static fn (array $row): int => (int) $row['candidateId'], $rows),
            true,
        );
    }
}
