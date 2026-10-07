<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\SavedSearchInput;
use App\ApiResource\Output\SavedSearchWithResultsOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SearchActivityRecorder;
use App\Domain\Search\SearchMembershipRecalculator;
use App\Domain\Search\SearchNewCountReader;
use App\Domain\Search\UnresolvedJobTermRecorder;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchProfileReviewRepository;
use App\Repository\SearchResultMembershipRepository;
use App\Security\SavedSearchVoter;
use App\State\CollaborationMapper;
use App\State\SearchResultsBuilder;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Écriture des recherches (task 072) : POST crée, PATCH modifie, DELETE supprime — réservés à
 * l'AUTEUR (SavedSearchVoter::WRITE). Une recherche partagée reste en lecture seule pour ses
 * destinataires : pas de co-édition, ils dupliquent.
 *
 * Auto-enregistrement : la recherche existe dès le premier critère posé, il n'y a pas d'action
 * « Enregistrer » côté produit.
 *
 * POST et PATCH renvoient la recherche AVEC son jeu de résultats : le recalcul a lieu ici de toute
 * façon, et l'appelant qui redemandait ensuite la fiche puis les résultats payait deux allers-retours
 * pour ce que cette réponse portait déjà. DELETE, lui, ne renvoie rien.
 *
 * @implements ProcessorInterface<SavedSearchInput|null, SavedSearchWithResultsOutput|null>
 */
final readonly class SavedSearchProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private SavedSearchRepository $searches,
        private EntityManagerInterface $em,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchMembershipRecalculator $recalculator,
        private SearchResultMembershipRepository $memberships,
        private SearchNewCountReader $newCounts,
        private SearchProfileReviewRepository $reviews,
        private FavoriteRepository $favorites,
        private SearchResultsBuilder $results,
        private UnresolvedJobTermRecorder $unresolvedJobTerms,
        private SearchActivityRecorder $activity,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SavedSearchWithResultsOutput
    {
        $searchId = $uriVariables['id'] ?? null;

        if (\is_string($searchId)) {
            return $this->update($data, $operation, $searchId);
        }

        return $this->create($data);
    }

    private function create(mixed $data): SavedSearchWithResultsOutput
    {
        if (!$data instanceof SavedSearchInput) {
            throw new UnprocessableEntityHttpException('Payload invalide.');
        }

        $recruiter = $this->currentRecruiter();
        $name = trim((string) $data->name);

        $search = new SavedSearch(
            $recruiter,
            $recruiter->organization,
            $name,
            $data->criteria ?? [],
            isAutoNamed: '' === $name,
            isShared: $data->isShared ?? false,
        );
        $this->em->persist($search);
        $this->em->flush();

        // Composer des critères, côté produit, c'est LANCER une recherche : c'est ici qu'un intitulé
        // que le dictionnaire ignore se constate, et nulle part ailleurs (task API-024).
        $this->unresolvedJobTerms->recordFrom($search->criteria);

        return $this->persistedOutput($search, $recruiter);
    }

    private function update(mixed $data, Operation $operation, string $searchId): ?SavedSearchWithResultsOutput
    {
        $search = $this->searches->findOneByUuid($searchId);
        if (!$search instanceof SavedSearch) {
            throw new NotFoundHttpException();
        }

        // Lire d'abord : un collègue qui ne PEUT PAS lire la recherche ne doit pas apprendre son
        // existence via un 403 (il reçoit un 404, comme si elle n'existait pas).
        if (!$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(SavedSearchVoter::WRITE, $search)) {
            throw new AccessDeniedHttpException();
        }

        if ($operation instanceof Delete) {
            $this->em->remove($search);
            $this->em->flush();

            return null;
        }

        if (!$data instanceof SavedSearchInput) {
            throw new UnprocessableEntityHttpException('Payload invalide.');
        }

        $recruiter = $this->currentRecruiter();

        // Une recherche close n'accepte plus que d'être rouverte. Sans ce verrou, « close » ne
        // serait qu'une étiquette : les critères continueraient de bouger dans le dos de son
        // auteur, et son bilan — tant de profils vus, tant de retenus — cesserait d'être un bilan.
        //
        // 409 et non 422 : la charge utile est parfaitement valide, c'est l'ÉTAT de la recherche
        // qui la refuse (cf. api-conventions.md, « déjà rattaché à une entreprise »).
        //
        // Placé après la suppression, qui reste permise : supprimer n'est pas modifier.
        if (null !== $search->closedAt
            && (null !== $data->name || null !== $data->criteria || null !== $data->isShared)) {
            throw new ConflictHttpException('Recherche close : rouvrez-la avant de la modifier.');
        }

        if (null !== $data->isClosed) {
            // Clôturer une recherche déjà close ne déplace pas sa date : c'est l'affirmation d'un
            // état, pas une transition, et un double clic ne doit rien réécrire.
            $search->closedAt = $data->isClosed
                ? $search->closedAt ?? new DateTimeImmutable()
                : null;
        }

        if (null !== $data->name) {
            // Vider le nom réactive le nom auto ; le renseigner le fige.
            $name = trim($data->name);
            $search->name = $name;
            $search->isAutoNamed = '' === $name;
        }
        if (null !== $data->criteria) {
            $search->criteria = $data->criteria;
            // Ajuster une recherche est un lancement comme un autre : c'est le même geste, et le
            // recruteur y tape le même genre d'intitulé.
            $this->unresolvedJobTerms->recordFrom($search->criteria);
        }
        if (null !== $data->isShared) {
            $search->isShared = $data->isShared;
        }

        $this->em->flush();

        return $this->persistedOutput($search, $recruiter);
    }

    /**
     * Le jeu de résultats est matérialisé dès l'écriture, pas seulement à la prochaine ingestion :
     * sans ça, tout le vivier matchant compterait comme « nouveau » au premier recalcul et le badge
     * afficherait un +N absurde. On pose donc aussi le repère de lecture de l'auteur — il vient de
     * composer ces critères, ce qu'ils ramènent n'est pas une nouveauté pour lui.
     *
     * Le jeu est construit APRÈS ce repère, et c'est voulu : aucune carte ne ressort « nouvelle » de
     * sa propre création. Première page seulement — le client a la liste complète des identifiants
     * pour savoir ce qu'il lui reste à charger, et une création n'a pas à peser tout le jeu.
     */
    private function persistedOutput(SavedSearch $search, Recruiter $recruiter): SavedSearchWithResultsOutput
    {
        $this->recalculator->recalculate($search);
        $this->newCounts->markViewed($search, $recruiter);
        // Créer, renommer, ajuster les critères, partager, clore ou ROUVRIR : tous passent ici, et
        // tous repoussent la clôture automatique. Rouvrir sans ce geste laisserait la recherche se
        // refermer à la passe suivante.
        $this->activity->record($search);

        return new SavedSearchWithResultsOutput(
            CollaborationMapper::toSavedSearch(
                $search,
                $recruiter,
                0,
                $this->memberships->count(['search' => $search]),
                $this->reviews->seenCountFor($search, $recruiter),
                $this->favorites->countFor($search),
            ),
            $this->results->build($search, $recruiter),
        );
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
