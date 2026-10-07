<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\PresencePeriodInput;
use App\ApiResource\Output\PresencePeriodOutput;
use App\Entity\Candidate;
use App\Entity\PresencePeriod;
use App\Repository\CandidateRepository;
use App\Repository\PresencePeriodRepository;
use App\Security\CandidateVoter;
use App\State\PresenceMapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Écriture des périodes de présence au Maroc (self-service, tâche 017) :
 *  POST   /v1/candidates/{candidateId}/presence-periods       → création
 *  PUT    /v1/candidates/{candidateId}/presence-periods/{id}  → mise à jour
 *  DELETE /v1/candidates/{candidateId}/presence-periods/{id}  → suppression
 *
 * Self-only (CandidateVoter) ou ROLE_ADMIN_*. Écriture locale uniquement : la présence Maroc reste
 * maître showcase-recruiter-webapp-saas et ne déclenche aucun push Jarvi.
 *
 * @implements ProcessorInterface<PresencePeriodInput|null, PresencePeriodOutput|null>
 */
final readonly class PresencePeriodProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
        private PresencePeriodRepository $periods,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?PresencePeriodOutput
    {
        $candidate = $this->resolveCandidate($uriVariables);

        if ($operation instanceof Delete) {
            $this->delete($candidate, $uriVariables);

            return null;
        }

        if (!$data instanceof PresencePeriodInput) {
            throw new BadRequestHttpException();
        }
        [$start, $end] = $this->dates($data);

        $period = isset($uriVariables['id'])
            ? $this->resolvePeriod($candidate, (string) $uriVariables['id'])
            : new PresencePeriod($candidate, $start, $end, $data->city);

        $period->startDate = $start;
        $period->endDate = $end;
        $period->city = $data->city;

        $this->em->persist($period);
        $this->em->flush();

        return PresenceMapper::toOutput($period);
    }

    private function delete(Candidate $candidate, array $uriVariables): void
    {
        $period = $this->resolvePeriod($candidate, (string) ($uriVariables['id'] ?? ''));
        $this->em->remove($period);
        $this->em->flush();
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function resolveCandidate(array $uriVariables): Candidate
    {
        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(CandidateVoter::EDIT, $candidate)) {
            throw new AccessDeniedHttpException();
        }

        return $candidate;
    }

    private function resolvePeriod(Candidate $candidate, string $uuid): PresencePeriod
    {
        $period = $this->periods->findOneByUuid($uuid);
        if (null === $period || $period->getCandidate()->getId() !== $candidate->getId()) {
            throw new NotFoundHttpException();
        }

        return $period;
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function dates(PresencePeriodInput $input): array
    {
        // La validation garantit non-null + endDate ≥ startDate ; garde défensive pour le typage.
        if (null === $input->startDate || null === $input->endDate) {
            throw new BadRequestHttpException();
        }

        return [$input->startDate, $input->endDate];
    }
}
