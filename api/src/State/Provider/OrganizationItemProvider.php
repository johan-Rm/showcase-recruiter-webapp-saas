<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\OrganizationOutput;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\OrganizationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/organizations/{uuid} : fiche entreprise. 404 si inconnue, 403 si non-membre (voter).
 *
 * @implements ProviderInterface<OrganizationOutput>
 */
final readonly class OrganizationItemProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?OrganizationOutput
    {
        $id = $uriVariables['id'] ?? null;
        $organization = \is_string($id) ? $this->organizations->findOneByUuid($id) : null;
        if (null === $organization) {
            return null;
        }

        if (!$this->security->isGranted(OrganizationVoter::VIEW, $organization)) {
            throw new AccessDeniedHttpException();
        }

        return OrganizationMapper::toOutput($organization);
    }
}
