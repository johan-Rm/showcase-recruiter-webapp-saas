<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\ProfileNoteInput;
use App\ApiResource\Output\ProfileNoteOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\ProfileNote;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Repository\CandidateRepository;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CollaborationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * POST /v1/organizations/{organizationId}/candidates/{candidateId}/notes : ajoute une note partagée.
 * Accès membre d'une organisation validée (OrganizationVoter::ACCESS) ; auteur = recruteur courant.
 *
 * @implements ProcessorInterface<ProfileNoteInput, ProfileNoteOutput>
 */
final readonly class ProfileNoteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProfileNoteOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }

        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveForOrganization($user, $organization)
            : null;
        if (!$recruiter instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        $note = new ProfileNote($organization, $candidate, $recruiter, $data->body);
        $this->em->persist($note);
        $this->em->flush();

        return CollaborationMapper::toNote($note);
    }
}
