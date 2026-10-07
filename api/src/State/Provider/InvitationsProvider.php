<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\InvitationOutput;
use App\Entity\Invitation;
use App\Repository\InvitationRepository;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\OrganizationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/organizations/{organizationId}/invitations : invitations de l'entreprise (admin entreprise, voter MANAGE).
 *
 * @implements ProviderInterface<CollectionOutput<InvitationOutput>>
 */
final readonly class InvitationsProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private InvitationRepository $invitations,
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
        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $items = array_map(
            static fn (Invitation $invitation): InvitationOutput => OrganizationMapper::toInvitation($invitation),
            $this->invitations->findByOrganization($organization),
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
