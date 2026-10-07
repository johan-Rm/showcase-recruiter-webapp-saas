<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\CandidateActivityProvider;

/**
 * Activité autour du profil candidat (tâche 019-A) : compteurs agrégés en lecture self-service.
 * Accès self-only (CandidateVoter) ou ROLE_ADMIN_*. Identité des entreprises jamais exposée en V1
 * (compteurs seuls). Cf. ESPACE_CANDIDAT.md §8, api-routes.md.
 */
#[ApiResource(
    shortName: 'CandidateActivity',
    operations: [
        new Get(
            uriTemplate: '/candidates/{candidateId}/activity',
            uriVariables: ['candidateId'],
            provider: CandidateActivityProvider::class,
        ),
    ],
)]
final class CandidateActivityOutput
{
    public function __construct(
        public int $profileViews,
        public int $reveals,
        public int $interestedCompanies,
    ) {
    }
}
