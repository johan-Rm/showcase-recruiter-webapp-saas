<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\ConsentOutput;
use App\Repository\CandidateRepository;
use App\Security\CandidateVoter;
use DateTimeInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/candidates/{candidateId}/consent : état de consentement courant du candidat (self-only).
 *
 * @implements ProviderInterface<ConsentOutput>
 */
final readonly class ConsentProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ConsentOutput
    {
        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(CandidateVoter::EDIT, $candidate)) {
            throw new AccessDeniedHttpException();
        }

        return new ConsentOutput(
            $candidate->hasConsent(),
            $candidate->consentGivenAt?->format(DateTimeInterface::ATOM),
            $candidate->consentSource?->value,
        );
    }
}
