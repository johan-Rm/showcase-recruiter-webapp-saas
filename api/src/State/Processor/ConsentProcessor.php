<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\ConsentInput;
use App\ApiResource\Output\ConsentOutput;
use App\Enum\ConsentSource;
use App\Repository\CandidateRepository;
use App\Security\CandidateVoter;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * PUT /v1/candidates/{candidateId}/consent : le candidat donne ou retire son consentement showcase-recruiter-webapp-saas
 * (self-service, tâche 033). Self-only (CandidateVoter) ou ROLE_ADMIN_*. Consentement explicite et
 * horodaté (RGPD) ; idempotent. Le consentement n'est jamais poussé vers Jarvi.
 *
 * @implements ProcessorInterface<ConsentInput|null, ConsentOutput>
 */
final readonly class ConsentProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ConsentOutput
    {
        if (!$data instanceof ConsentInput || null === $data->consent) {
            throw new BadRequestHttpException();
        }

        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(CandidateVoter::EDIT, $candidate)) {
            throw new AccessDeniedHttpException();
        }

        $data->consent
            ? $candidate->giveConsent(ConsentSource::SELF_SERVICE, new DateTimeImmutable())
            : $candidate->withdrawConsent();
        $this->em->flush();

        return new ConsentOutput(
            $candidate->hasConsent(),
            $candidate->consentGivenAt?->format(DateTimeInterface::ATOM),
            $candidate->consentSource?->value,
        );
    }
}
