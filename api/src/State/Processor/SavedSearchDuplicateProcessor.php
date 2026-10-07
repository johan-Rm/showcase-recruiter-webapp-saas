<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Output\SavedSearchOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SearchMembershipRecalculator;
use App\Domain\Search\SearchNewCountReader;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchResultMembershipRepository;
use App\Security\SavedSearchVoter;
use App\State\CollaborationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /v1/searches/{id}/duplicate — accessible à quiconque peut LIRE la recherche (task 072).
 *
 * C'est la seule prise du destinataire d'une recherche partagée : il ne peut pas l'éditer, il s'en
 * fait une copie qui lui appartient. La copie n'est jamais partagée d'office — partager reste une
 * décision de son nouvel auteur.
 *
 * Ni les favoris, ni les crédits consommés ne suivent : on duplique des CRITÈRES,
 * pas un travail de tri.
 *
 * @implements ProcessorInterface<null, SavedSearchOutput>
 */
final readonly class SavedSearchDuplicateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private SavedSearchRepository $searches,
        private EntityManagerInterface $em,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchMembershipRecalculator $recalculator,
        private SearchResultMembershipRepository $memberships,
        private SearchNewCountReader $newCounts,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SavedSearchOutput
    {
        $searchId = $uriVariables['id'] ?? null;
        $source = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;
        if (!$source instanceof SavedSearch) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(SavedSearchVoter::READ, $source)) {
            throw new NotFoundHttpException();
        }

        $recruiter = $this->currentRecruiter();
        if ($recruiter->organization->getId() !== $source->organization->getId()) {
            throw new UnprocessableEntityHttpException('Recherche hors de votre organisation.');
        }

        $copy = new SavedSearch(
            $recruiter,
            $recruiter->organization,
            $source->name,
            $source->criteria,
            isAutoNamed: $source->isAutoNamed,
            isShared: false,
        );
        $this->em->persist($copy);
        $this->em->flush();

        $this->recalculator->recalculate($copy);
        $this->newCounts->markViewed($copy, $recruiter);

        return CollaborationMapper::toSavedSearch(
            $copy,
            $recruiter,
            0,
            $this->memberships->count(['search' => $copy]),
            // La copie ne reprend que le nom et les critères : elle n'a ni profil vu ni profil
            // retenu, et deux requêtes le confirmeraient sans rien apprendre.
            0,
            0,
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
