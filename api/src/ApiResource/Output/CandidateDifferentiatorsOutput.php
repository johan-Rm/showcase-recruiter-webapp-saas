<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\DifferentiatorsInput;
use App\State\Processor\DifferentiatorsProcessor;
use App\State\Provider\DifferentiatorsProvider;

/**
 * Différenciants candidat (self-service, tâche 016) : maîtres côté showcase-recruiter-webapp-saas, lus et édités sous le
 * candidat. Accès self-only (CandidateVoter) ou ROLE_ADMIN_*. Ces données restent locales et ne
 * déclenchent aucun push Jarvi. Cf. api-routes.md.
 */
#[ApiResource(
    shortName: 'CandidateDifferentiators',
    operations: [
        new Get(
            uriTemplate: '/candidates/{candidateId}/differentiators',
            uriVariables: ['candidateId'],
            provider: DifferentiatorsProvider::class,
        ),
        new Put(
            uriTemplate: '/candidates/{candidateId}/differentiators',
            uriVariables: ['candidateId'],
            input: DifferentiatorsInput::class,
            read: false,
            processor: DifferentiatorsProcessor::class,
        ),
    ],
)]
final class CandidateDifferentiatorsOutput
{
    /**
     * @param array{value: int, currency: string} $salaryExpected
     * @param array{value: int, currency: string} $salaryMin
     */
    public function __construct(
        public ?string $availability,
        public ?string $returnHorizon,
        public bool $geoMobility,
        public array $salaryExpected,
        public array $salaryMin,
    ) {
    }
}
