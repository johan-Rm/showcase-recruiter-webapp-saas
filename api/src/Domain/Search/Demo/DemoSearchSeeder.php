<?php

declare(strict_types=1);

namespace App\Domain\Search\Demo;

use App\Domain\Search\SearchMembershipRecalculator;
use App\Entity\Candidate;
use App\Entity\Favorite;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchProfileReview;
use App\Entity\SearchResultMembership;
use App\Entity\SearchView;
use App\Enum\OrganizationRole;
use App\Enum\OrganizationValidationStatus;
use App\Repository\FavoriteRepository;
use App\Repository\SearchProfileReviewRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Pose les recherches de démonstration du catalogue et leur fabrique un passé — task API-029.
 *
 * Le jeu de résultats n'est JAMAIS écrit à la main : il est produit par le recalculateur, celui-là
 * même qui tourne après une ingestion. C'est la seule façon d'obtenir des cartes qui annoncent
 * exactement ce que la Table servira — un nombre inventé ferait chercher un bug inexistant.
 *
 * Ce que le seeder fabrique, en revanche, c'est le PASSÉ que ce recalcul ne peut pas produire : les
 * dates d'entrée dans le jeu sont reculées, quelques-unes ramenées après le repère de lecture (les
 * nouveautés), et les profils vus comme retenus reçoivent leurs lignes. Sans ce passé, toutes les
 * cartes se ressembleraient — jeu complet, rien de vu, rien de retenu.
 *
 * Rejouable : une recherche est reconnue à ses CRITÈRES, jamais à son nom — celui-ci est composé par
 * l'écran et vaut le plus souvent la chaîne vide en base.
 *
 * Rejouer COMPLÈTE, et ne retranche rien : les profils vus et retenus d'une exécution précédente
 * restent. Après un import qui agrandit le vivier, c'est ce qu'on veut — le parcours déjà fait ne
 * s'efface pas. Après une refonte du catalogue, en revanche, les compteurs peuvent dépasser ce que
 * la nouvelle progression annonce : c'est la base qu'il faut alors reprendre, pas la commande.
 */
final class DemoSearchSeeder
{
    /** Âge donné aux entrées ordinaires du jeu de résultats : bien avant le dernier passage. */
    private const int BACKGROUND_AGE_DAYS = 45;

    /** Dernier passage du recruteur sur la recherche — le repère dont dépend le badge « +N ». */
    private const int VIEWED_DAYS_AGO = 7;

    /** Âge des entrées qui doivent compter comme nouvelles : après le repère ci-dessus. */
    private const int FRESH_DAYS_AGO = 2;

    private const int CLOSED_DAYS_AGO = 12;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DemoSearchCatalog $catalog,
        private readonly SearchMembershipRecalculator $recalculator,
        private readonly SearchProfileReviewRepository $reviews,
        private readonly FavoriteRepository $favorites,
    ) {
    }

    public function seed(?string $recruiterEmail = null): DemoSearchReport
    {
        $owner = $this->resolveOwner($recruiterEmail);
        $colleague = $this->resolveColleague($owner);

        $created = 0;
        $reused = 0;
        $lines = [];

        foreach ($this->catalog->definitions() as $index => $definition) {
            $author = $definition->byColleague && $colleague instanceof Recruiter ? $colleague : $owner;

            $search = $this->findByCriteria($author, $definition->criteria);
            $isNew = !$search instanceof SavedSearch;
            if ($isNew) {
                $search = new SavedSearch($author, $author->organization, $definition->name, $definition->criteria);
                $this->em->persist($search);
                ++$created;
            } else {
                ++$reused;
            }

            $search->name = $definition->name;
            $search->isAutoNamed = '' === $definition->name;
            $search->isShared = $definition->shared;
            $search->closedAt = $definition->closed
                ? new DateTimeImmutable(\sprintf('-%d days', self::CLOSED_DAYS_AGO))
                : null;
            $this->em->flush();

            // Le jeu de résultats, produit par le moteur réel. Une recherche sans critère n'en a
            // aucun, et c'est voulu : le vivier entier n'est pas un résultat de recherche.
            $this->recalculator->recalculate($search);

            $candidateIds = $this->resultCandidateIds($search);
            $shares = $this->catalog->progressAt($index)->sharesFor(\count($candidateIds));

            // Trois tranches PRISES DANS LE MÊME ORDRE que la pile, et disjointes là où le récit
            // l'exige : les nouveaux arrivants sont en queue, donc pas encore vus ; les retenus sont
            // pris parmi les vus, parce qu'on ne met pas de côté un profil qu'on n'a pas ouvert.
            $fresh = \array_slice($candidateIds, \count($candidateIds) - $shares['fresh']);
            $seen = \array_slice($candidateIds, 0, $shares['seen']);
            $retained = \array_slice($candidateIds, 0, $shares['retained']);

            $this->ageMemberships($search, $fresh);
            $this->markSeen($search, $owner, $seen);
            $this->addFavorites($search, $author, $retained);
            $this->placeViewMarker($search, $owner);

            $this->em->flush();

            // Les deux comptages sont relus PAR LES DÉPÔTS QUI SERVENT LA CARTE, et non déduits de
            // ce qui vient d'être écrit : le compte rendu de la commande annonce alors exactement ce
            // que l'écran affichera, y compris ce qu'une exécution précédente avait laissé.
            $searchId = (int) $search->getId();
            $lines[] = new DemoSearchLine(
                $this->label($definition),
                \count($candidateIds),
                $search->closedAt instanceof DateTimeImmutable ? 0 : \count($fresh),
                $this->reviews->seenCountsFor([$search], $owner)[$searchId] ?? 0,
                $this->favorites->countsFor([$search])[$searchId] ?? 0,
                $isNew ? 'créée' : 'déjà présente',
                $definition->closed,
                $definition->shared,
                $definition->byColleague && $colleague instanceof Recruiter,
            );
        }

        return new DemoSearchReport(
            (string) $owner->getPerson()->contactEmail,
            $colleague instanceof Recruiter ? (string) $colleague->getPerson()->contactEmail : null,
            $owner->organization->name,
            $created,
            $reused,
            $lines,
        );
    }

    /**
     * Le recruteur qui portera la grille. Sans adresse fournie, le premier propriétaire d'une
     * entreprise validée — c'est le compte avec lequel on ouvre l'espace entreprise.
     */
    private function resolveOwner(?string $email): Recruiter
    {
        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from(Recruiter::class, 'r')
            ->join('r.person', 'p')
            ->join('r.organization', 'o')
            ->where('o.validationStatus = :validated')
            ->setParameter('validated', OrganizationValidationStatus::VALIDEE)
            ->orderBy('r.id', 'ASC');

        if (null !== $email && '' !== $email) {
            $qb->andWhere('LOWER(p.contactEmail) = :email')->setParameter('email', mb_strtolower($email));
        }

        /** @var list<Recruiter> $recruiters */
        $recruiters = $qb->getQuery()->getResult();
        if ([] === $recruiters) {
            $message = null !== $email && '' !== $email
                ? \sprintf('Aucun recruteur « %s » dans une entreprise validée.', $email)
                : 'Aucun recruteur dans une entreprise validée : lancez d\'abord les fixtures ou app:accounts:seed-team.';

            throw new RuntimeException($message);
        }

        foreach ($recruiters as $recruiter) {
            if (OrganizationRole::COMPANY_OWNER === $recruiter->organizationRole) {
                return $recruiter;
            }
        }

        return $recruiters[0];
    }

    /**
     * Un collègue de la même entreprise, s'il en existe un : sans lui, pas de carte en lecture
     * seule — on ne peut pas recevoir sa propre recherche.
     */
    private function resolveColleague(Recruiter $owner): ?Recruiter
    {
        /** @var list<Recruiter> $others */
        $others = $this->em->createQueryBuilder()
            ->select('r')
            ->from(Recruiter::class, 'r')
            ->where('r.organization = :organization')
            ->andWhere('r.id != :owner')
            ->setParameter('organization', $owner->organization)
            ->setParameter('owner', $owner->getId())
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $others[0] ?? null;
    }

    /**
     * La recherche du catalogue déjà en base, reconnue à ses critères — comparés dans l'ordre, qui
     * est signifiant.
     *
     * Cette identification protège aussi les recherches d'essai laissées par un développeur : le
     * catalogue en pose toujours au moins deux, là où un essai n'en porte qu'un.
     *
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    private function findByCriteria(Recruiter $owner, array $criteria): ?SavedSearch
    {
        /** @var list<SavedSearch> $searches */
        $searches = $this->em->getRepository(SavedSearch::class)->findBy(['owner' => $owner]);

        foreach ($searches as $search) {
            if ($search->criteria === $criteria) {
                return $search;
            }
        }

        return null;
    }

    /**
     * Les candidats du jeu de résultats, dans l'ordre où la Table les servira — le mieux classé
     * d'abord. Ce qui est « vu » et « retenu » part donc du haut de la pile, comme en vrai.
     *
     * @return list<int> ids internes de candidats
     */
    private function resultCandidateIds(SavedSearch $search): array
    {
        /** @var list<array{candidateId: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(m.candidate) AS candidateId')
            ->from(SearchResultMembership::class, 'm')
            ->where('m.search = :search')
            ->setParameter('search', $search)
            ->orderBy('m.matchedCount', 'DESC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['candidateId'], $rows);
    }

    /**
     * Recule les dates d'entrée dans le jeu, puis ramène celles des profils `$fresh` après le repère
     * de lecture.
     *
     * Le recalcul date toutes les entrées de MAINTENANT : sans ce vieillissement, chaque carte
     * annoncerait son jeu entier comme nouveau, et le badge « +N » ne se distinguerait jamais du
     * volume.
     *
     * @param list<int> $fresh ids internes des candidats qui doivent compter comme nouveaux
     */
    private function ageMemberships(SavedSearch $search, array $fresh): void
    {
        $this->em->createQuery(
            'UPDATE '.SearchResultMembership::class.' m SET m.firstMatchedAt = :date WHERE m.search = :search'
        )
            ->setParameter('date', new DateTimeImmutable(\sprintf('-%d days', self::BACKGROUND_AGE_DAYS)))
            ->setParameter('search', $search)
            ->execute();

        if ([] === $fresh) {
            return;
        }

        $this->em->createQuery(
            'UPDATE '.SearchResultMembership::class.' m SET m.firstMatchedAt = :date '
            .'WHERE m.search = :search AND m.candidate IN (:candidates)'
        )
            ->setParameter('date', new DateTimeImmutable(\sprintf('-%d days', self::FRESH_DAYS_AGO)))
            ->setParameter('search', $search)
            ->setParameter('candidates', $fresh)
            ->execute();
    }

    /**
     * Marque comme vus les profils donnés — le haut de la pile, celui qu'on parcourt d'abord.
     *
     * @param list<int> $candidateIds
     */
    private function markSeen(SavedSearch $search, Recruiter $recruiter, array $candidateIds): void
    {
        if ([] === $candidateIds) {
            return;
        }

        /** @var list<array{candidateId: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(v.candidate) AS candidateId')
            ->from(SearchProfileReview::class, 'v')
            ->where('v.search = :search')
            ->andWhere('v.recruiter = :recruiter')
            ->setParameter('search', $search)
            ->setParameter('recruiter', $recruiter)
            ->getQuery()
            ->getScalarResult();

        $already = array_fill_keys(
            array_map(static fn (array $row): int => (int) $row['candidateId'], $rows),
            true,
        );

        $seenAt = new DateTimeImmutable(\sprintf('-%d days', self::VIEWED_DAYS_AGO));
        foreach ($candidateIds as $candidateId) {
            if (isset($already[$candidateId])) {
                continue;
            }
            $this->em->persist(new SearchProfileReview(
                $search,
                $this->em->getReference(Candidate::class, $candidateId),
                $recruiter,
                seenAt: $seenAt,
            ));
        }
    }

    /**
     * Retient les premiers profils de la pile. Les favoris sont posés par l'AUTEUR : sur une
     * recherche partagée, le lecteur ne peut pas y écrire.
     *
     * @param list<int> $candidateIds
     */
    private function addFavorites(SavedSearch $search, Recruiter $author, array $candidateIds): void
    {
        if ([] === $candidateIds) {
            return;
        }

        /** @var list<array{candidateId: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(f.candidate) AS candidateId')
            ->from(Favorite::class, 'f')
            ->where('f.search = :search')
            ->setParameter('search', $search)
            ->getQuery()
            ->getScalarResult();

        $already = array_fill_keys(
            array_map(static fn (array $row): int => (int) $row['candidateId'], $rows),
            true,
        );

        foreach ($candidateIds as $candidateId) {
            if (isset($already[$candidateId])) {
                continue;
            }
            $this->em->persist(new Favorite(
                $search,
                $this->em->getReference(Candidate::class, $candidateId),
                $author,
            ));
        }
    }

    /** Le dernier passage du recruteur : sans lui, tout le jeu compterait comme nouveau. */
    private function placeViewMarker(SavedSearch $search, Recruiter $recruiter): void
    {
        $viewedAt = new DateTimeImmutable(\sprintf('-%d days', self::VIEWED_DAYS_AGO));

        $view = $this->em->getRepository(SearchView::class)
            ->findOneBy(['search' => $search, 'recruiter' => $recruiter]);

        if ($view instanceof SearchView) {
            $view->lastViewedAt = $viewedAt;

            return;
        }

        $this->em->persist(new SearchView($search, $recruiter, $viewedAt));
    }

    /** De quoi nommer la recherche dans le compte rendu de la commande. */
    private function label(DemoSearchDefinition $definition): string
    {
        if ('' !== $definition->name) {
            return $definition->name;
        }

        foreach ($definition->criteria as $criterion) {
            if ('job' === $criterion['facet']) {
                return $criterion['value'];
            }
        }

        return '(brouillon sans critère)';
    }
}
