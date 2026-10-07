<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\Repository\CandidateRepository;
use App\Security\CandidateVoter;
use App\State\PresenceMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/candidates/{candidateId}/presence-periods : périodes de présence du candidat (self-only).
 *
 * @implements ProviderInterface<CollectionOutput>
 */
final readonly class PresencePeriodsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(CandidateVoter::EDIT, $candidate)) {
            throw new AccessDeniedHttpException();
        }

        $items = [];
        foreach ($candidate->getPresencePeriods() as $period) {
            $items[] = PresenceMapper::toOutput($period);
        }

        return new CollectionOutput($items, \count($items));
    }
}
