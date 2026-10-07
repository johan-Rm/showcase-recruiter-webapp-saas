<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminCandidateDetailOutput;
use App\Repository\CandidateRepository;
use App\State\CandidateMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/admin/candidates/{id} : fiche candidat détaillée, NON masquée, réservée aux rôles admin.
 * Garde ROLE_ADMIN explicite (accès cross-tenant). Aucune logique de dévoilement : l'admin voit tout.
 *
 * @implements ProviderInterface<AdminCandidateDetailOutput>
 */
final readonly class AdminCandidateItemProvider implements ProviderInterface
{
    public function __construct(
        private CandidateRepository $candidates,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminCandidateDetailOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $id = $uriVariables['id'] ?? null;
        $candidate = \is_string($id) ? $this->candidates->findOneByUuid($id) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }

        return CandidateMapper::toAdminDetail($candidate);
    }
}
