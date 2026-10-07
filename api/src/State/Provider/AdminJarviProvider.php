<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminJarviOutput;
use App\Entity\JarviSyncState;
use App\Repository\CandidateRepository;
use App\Repository\JarviSyncStateRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/jarvi : supervision de l'ingestion, réservée aux rôles admin. Garde ROLE_ADMIN
 * explicite (accès cross-tenant). État des flux + agrégats du vivier, calculés à la volée.
 *
 * @implements ProviderInterface<AdminJarviOutput>
 */
final readonly class AdminJarviProvider implements ProviderInterface
{
    public function __construct(
        private JarviSyncStateRepository $syncStates,
        private CandidateRepository $candidates,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminJarviOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $streams = array_map(
            static fn (JarviSyncState $state): array => [
                'name' => $state->name,
                'cursor' => $state->cursor?->format('c'),
                'lastRunAt' => $state->lastRunAt?->format('c'),
            ],
            $this->syncStates->findAll(),
        );

        $stats = $this->candidates->vivierStats();

        return new AdminJarviOutput(
            $streams,
            $stats['total'],
            $stats['active'],
            $stats['inactive'],
            $stats['withConsent'],
            $stats['synced'],
            $stats['pending'],
            $stats['failed'],
        );
    }
}
