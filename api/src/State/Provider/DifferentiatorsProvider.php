<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CandidateDifferentiatorsOutput;
use App\Repository\CandidateRepository;
use App\Security\CandidateVoter;
use App\State\CandidateDifferentiatorsMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/candidates/{candidateId}/differentiators : différenciants courants du candidat (self-only,
 * tâche 016).
 *
 * @implements ProviderInterface<CandidateDifferentiatorsOutput>
 */
final readonly class DifferentiatorsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CandidateDifferentiatorsOutput
    {
        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(CandidateVoter::EDIT, $candidate)) {
            throw new AccessDeniedHttpException();
        }

        return CandidateDifferentiatorsMapper::toOutput($candidate);
    }
}
