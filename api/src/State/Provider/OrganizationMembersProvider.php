<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\RecruiterOutput;
use App\Entity\Recruiter;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\OrganizationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/organizations/{uuid}/members : recruteurs de l'entreprise (enveloppe plate).
 * 404 si entreprise inconnue, 403 si non-membre (voter).
 *
 * @implements ProviderInterface<CollectionOutput<RecruiterOutput>>
 */
final readonly class OrganizationMembersProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $id = $uriVariables['id'] ?? null;
        $organization = \is_string($id) ? $this->organizations->findOneByUuid($id) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(OrganizationVoter::VIEW, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $items = array_map(
            static fn (Recruiter $recruiter): RecruiterOutput => OrganizationMapper::toMember($recruiter),
            $organization->getRecruiters()->toArray(),
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
