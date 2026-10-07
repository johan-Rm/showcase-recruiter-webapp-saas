<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\DifferentiatorsInput;
use App\ApiResource\Output\CandidateDifferentiatorsOutput;
use App\Entity\MonetaryAmount;
use App\Repository\CandidateRepository;
use App\Security\CandidateVoter;
use App\State\CandidateDifferentiatorsMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * PUT /v1/candidates/{candidateId}/differentiators : le candidat édite ses différenciants (maîtres
 * showcase-recruiter-webapp-saas, tâche 016). Self-only (CandidateVoter) ou ROLE_ADMIN_*. L'écriture est locale uniquement :
 * aucun push Jarvi n'est déclenché.
 *
 * @implements ProcessorInterface<DifferentiatorsInput|null, CandidateDifferentiatorsOutput>
 */
final readonly class DifferentiatorsProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CandidateDifferentiatorsOutput
    {
        if (!$data instanceof DifferentiatorsInput) {
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

        $candidate->availability = $data->availability;
        $candidate->returnHorizon = $data->returnHorizon;
        $candidate->geoMobility = $data->geoMobility;
        if (null !== $data->salaryExpected) {
            $candidate->salaryExpected = new MonetaryAmount($data->salaryExpected->value, $data->salaryExpected->currency);
        }
        if (null !== $data->salaryMin) {
            $candidate->salaryMin = new MonetaryAmount($data->salaryMin->value, $data->salaryMin->currency);
        }

        $this->em->flush();

        return CandidateDifferentiatorsMapper::toOutput($candidate);
    }
}
