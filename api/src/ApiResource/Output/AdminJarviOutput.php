<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminJarviProvider;

/**
 * Instantané de supervision de l'ingestion Jarvi (tâche 101) : état des flux de synchro (curseur,
 * dernier run) + volumes du vivier par état (actifs / désactivés par la réconciliation, statut de
 * synchro, consentement). Lecture seule, gardée ROLE_ADMIN.
 *
 * Pas d'historique de runs : `IngestionReport` est un objet transient (non persisté). La relance
 * on-demand (POST) viendra avec la chaîne asynchrone (tâche 080).
 */
#[ApiResource(
    shortName: 'AdminJarvi',
    operations: [
        new Get(
            uriTemplate: '/admin/jarvi',
            name: 'admin_jarvi',
            provider: AdminJarviProvider::class,
        ),
    ],
)]
final class AdminJarviOutput
{
    /** @param list<array{name: string, cursor: ?string, lastRunAt: ?string}> $streams */
    public function __construct(
        public array $streams,
        public int $total,
        public int $active,
        public int $inactive,
        public int $withConsent,
        public int $synced,
        public int $pending,
        public int $failed,
    ) {
    }
}
