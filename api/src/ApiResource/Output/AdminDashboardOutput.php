<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminDashboardProvider;

/**
 * Centre de contrôle admin (tâche 102) : les compteurs temps réel de la home — file de revue,
 * connexions, dévoilements, inscriptions sur une fenêtre glissante de 7 jours, plus le volume du
 * vivier. Lecture seule, gardée ROLE_ADMIN.
 */
#[ApiResource(
    shortName: 'AdminDashboard',
    operations: [
        new Get(
            uriTemplate: '/admin/dashboard',
            name: 'admin_dashboard',
            provider: AdminDashboardProvider::class,
        ),
    ],
)]
final class AdminDashboardOutput
{
    public function __construct(
        /** Entreprises en attente de décision — la seule file de travail réelle de l'admin. */
        public int $organizationsInReview,
        public int $loginsLast7Days,
        public int $revealsLast7Days,
        public int $signupsLast7Days,
        public int $candidatesTotal,
        public int $candidatesActive,
    ) {
    }
}
