<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\RevealInput;
use App\ApiResource\Output\RevealedProfileOutput;
use App\Domain\Candidate\CandidateExposurePolicy;
use App\Domain\Credit\InsufficientCreditsException;
use App\Domain\Credit\ProfileRevealer;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Entity\User;
use App\Enum\RevealType;
use App\Repository\CandidateRepository;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CreditMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /v1/organizations/{organizationId}/revealed-profiles : dévoile un candidat (débit 1 crédit).
 * Délègue les invariants au Domain\ProfileRevealer (atomique, idempotent, solde ≥ 0).
 *
 * @implements ProcessorInterface<RevealInput, RevealedProfileOutput>
 */
final readonly class RevealProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private CandidateRepository $candidates,
        private ProfileRevealer $revealer,
        private CandidateExposurePolicy $candidateExposurePolicy,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RevealedProfileOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $candidate = $this->candidates->findOneByUuid($data->candidateId);
        if (null === $candidate) {
            throw new NotFoundHttpException('Candidat introuvable.');
        }
        if (!$this->candidateExposurePolicy->isExposable($candidate)) {
            throw new NotFoundHttpException('Candidat introuvable.');
        }

        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveForOrganization($user, $organization)
            : null;
        if (!$recruiter instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        try {
            $revealed = $this->revealer->reveal($organization, $candidate, $recruiter, RevealType::from($data->type));
        } catch (InsufficientCreditsException $e) {
            throw new HttpException(Response::HTTP_PAYMENT_REQUIRED, $e->getMessage(), $e);
        }

        // RIEN À SERVIR SUR CET ACCÈS : la demande est recevable mais sans objet, et rien n'a été
        // débité. Un refus explicite plutôt qu'une réponse vide — la ressource attendue est UN
        // dévoilement, et il n'y en a pas. Le lot, lui, rend simplement une collection plus courte.
        if (!$revealed instanceof RevealedProfile) {
            throw new UnprocessableEntityHttpException('Ce profil n’a rien à dévoiler sur cet accès.');
        }

        return CreditMapper::toRevealed($revealed);
    }
}
