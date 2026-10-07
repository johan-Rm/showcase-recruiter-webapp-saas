<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\AssignmentInput;
use App\ApiResource\Output\AssignmentOutput;
use App\Domain\Organization\AssigneeNotInOrganizationException;
use App\Domain\Organization\CandidateAssigner;
use App\Domain\Organization\CandidateNotRevealedException;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\CandidateAssignment;
use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Repository\CandidateRepository;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CollaborationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * PUT/DELETE /assignments/{candidateId}. L'assignation est partagée par l'organisation.
 *
 * @implements ProcessorInterface<AssignmentInput|null, AssignmentOutput|null>
 */
final readonly class AssignmentProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
        private CandidateAssigner $assigner,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AssignmentOutput
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
            throw new NotFoundHttpException('Candidat introuvable.');
        }

        $repository = $this->em->getRepository(CandidateAssignment::class);
        if ($operation instanceof Delete) {
            $assignment = $repository->findOneBy(['organization' => $organization, 'candidate' => $candidate]);
            if ($assignment instanceof CandidateAssignment) {
                $this->em->remove($assignment);
                $this->em->flush();
            }

            return null;
        }

        if (!$data instanceof AssignmentInput) {
            throw new UnprocessableEntityHttpException('Payload invalide.');
        }

        $assignedBy = $this->currentRecruiter($organization);
        $assignee = Uuid::isValid($data->assigneeRecruiterId)
            ? $this->em->getRepository(Recruiter::class)->findOneBy(['uuid' => Uuid::fromString($data->assigneeRecruiterId)])
            : null;
        if (!$assignee instanceof Recruiter) {
            throw new UnprocessableEntityHttpException('Recruteur assigné introuvable.');
        }

        try {
            $assignment = $this->assigner->assign($organization, $candidate, $assignee, $assignedBy);
        } catch (AssigneeNotInOrganizationException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        } catch (CandidateNotRevealedException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        $this->em->flush();

        return CollaborationMapper::toAssignment($assignment);
    }

    private function currentRecruiter(Organization $organization): Recruiter
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveForOrganization($user, $organization)
            : null;
        if (!$recruiter instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        return $recruiter;
    }
}
