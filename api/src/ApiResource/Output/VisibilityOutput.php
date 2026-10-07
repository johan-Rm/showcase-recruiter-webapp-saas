<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\VisibilityInput;
use App\State\Processor\VisibilityProcessor;
use App\State\Provider\VisibilityProvider;

/**
 * Visibilité d'un candidat (self-service, tâche 018). Lecture + mise à jour sous le candidat ;
 * accès self-only (CandidateVoter) ou ROLE_ADMIN_*. `visibilityUpdatedAt` = trace de consentement.
 */
#[ApiResource(
    shortName: 'CandidateVisibility',
    operations: [
        new Get(
            uriTemplate: '/candidates/{candidateId}/visibility',
            uriVariables: ['candidateId'],
            provider: VisibilityProvider::class,
        ),
        new Put(
            uriTemplate: '/candidates/{candidateId}/visibility',
            uriVariables: ['candidateId'],
            input: VisibilityInput::class,
            read: false,
            processor: VisibilityProcessor::class,
        ),
    ],
)]
final class VisibilityOutput
{
    public function __construct(
        public string $visibility,
        public ?string $visibilityUpdatedAt,
    ) {
    }
}
