<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AssignmentOutput;
use App\ApiResource\Output\CollectionOutput;
use App\Entity\CandidateAssignment;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CollaborationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/organizations/{organizationId}/assignments : responsables candidats partagés.
 *
 * @implements ProviderInterface<CollectionOutput<AssignmentOutput>>
 */
final readonly class AssignmentsProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $assignments = $this->em->getRepository(CandidateAssignment::class)
            ->findBy(['organization' => $organization], ['assignedAt' => 'DESC', 'id' => 'DESC']);

        $items = array_map(
            static fn (CandidateAssignment $assignment): AssignmentOutput => CollaborationMapper::toAssignment($assignment),
            $assignments,
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
