<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CandidateActivityOutput;
use App\Domain\Candidate\CandidateActivityReader;
use App\Repository\CandidateRepository;
use App\Security\CandidateVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/candidates/{candidateId}/activity : compteurs d'activité du candidat (self-only,
 * tâche 019-A). L'anonymisation est garantie côté serveur : seuls des agrégats sont renvoyés.
 *
 * @implements ProviderInterface<CandidateActivityOutput>
 */
final readonly class CandidateActivityProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
        private CandidateActivityReader $activity,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CandidateActivityOutput
    {
        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(CandidateVoter::EDIT, $candidate)) {
            throw new AccessDeniedHttpException();
        }

        $counts = $this->activity->read($candidate);

        return new CandidateActivityOutput(
            profileViews: $counts['profileViews'],
            reveals: $counts['reveals'],
            interestedCompanies: $counts['interestedCompanies'],
        );
    }
}
