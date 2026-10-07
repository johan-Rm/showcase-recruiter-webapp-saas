<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\ConsentInput;
use App\State\Processor\ConsentProcessor;
use App\State\Provider\ConsentProvider;

/**
 * Consentement showcase-recruiter-webapp-saas d'un candidat (self-service, tâche 033). Lecture + mise à jour sous le
 * candidat ; accès self-only (CandidateVoter) ou ROLE_ADMIN_*. `consentGivenAt` est la trace RGPD
 * qui conditionne l'exposition du profil aux recruteurs.
 */
#[ApiResource(
    shortName: 'CandidateConsent',
    operations: [
        new Get(
            uriTemplate: '/candidates/{candidateId}/consent',
            uriVariables: ['candidateId'],
            provider: ConsentProvider::class,
        ),
        new Put(
            uriTemplate: '/candidates/{candidateId}/consent',
            uriVariables: ['candidateId'],
            input: ConsentInput::class,
            read: false,
            processor: ConsentProcessor::class,
        ),
    ],
)]
final class ConsentOutput
{
    public function __construct(
        public bool $consent,
        public ?string $consentGivenAt,
        public ?string $consentSource,
    ) {
    }
}
