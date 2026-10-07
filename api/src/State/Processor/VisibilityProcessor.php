<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\VisibilityInput;
use App\ApiResource\Output\VisibilityOutput;
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
 * PUT /v1/candidates/{candidateId}/visibility : le candidat choisit son exposition (self-service,
 * tâche 018). Self-only (CandidateVoter) ou ROLE_ADMIN_*. Horodate le consentement
 * (`visibilityUpdatedAt`). La visibilité est interne (appliquée à la recherche), non poussée à Jarvi.
 *
 * @implements ProcessorInterface<VisibilityInput|null, VisibilityOutput>
 */
final readonly class VisibilityProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): VisibilityOutput
    {
        if (!$data instanceof VisibilityInput || null === $data->visibility) {
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

        $candidate->visibility = $data->visibility;
        $candidate->visibilityUpdatedAt = new DateTimeImmutable();
        $this->em->flush();

        return new VisibilityOutput(
            $candidate->visibility->value,
            $candidate->visibilityUpdatedAt->format(DateTimeInterface::ATOM),
        );
    }
}
