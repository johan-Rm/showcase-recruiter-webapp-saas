<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\PresencePeriodInput;
use App\State\Processor\PresencePeriodProcessor;
use App\State\Provider\PresencePeriodsProvider;

/**
 * Période de présence au Maroc d'un candidat (self-service, tâche 017). Routes sous le candidat ;
 * accès self-only (CandidateVoter) ou ROLE_ADMIN_*. `id` non-identifiant (réponse plate).
 */
#[ApiResource(
    shortName: 'PresencePeriod',
    operations: [
        new Get(
            uriTemplate: '/candidates/{candidateId}/presence-periods',
            uriVariables: ['candidateId'],
            output: CollectionOutput::class,
            provider: PresencePeriodsProvider::class,
            paginationEnabled: false,
        ),
        new Post(
            uriTemplate: '/candidates/{candidateId}/presence-periods',
            uriVariables: ['candidateId'],
            input: PresencePeriodInput::class,
            processor: PresencePeriodProcessor::class,
        ),
        new Put(
            uriTemplate: '/candidates/{candidateId}/presence-periods/{id}',
            uriVariables: ['candidateId', 'id'],
            input: PresencePeriodInput::class,
            read: false,
            processor: PresencePeriodProcessor::class,
        ),
        new Delete(
            uriTemplate: '/candidates/{candidateId}/presence-periods/{id}',
            uriVariables: ['candidateId', 'id'],
            read: false,
            processor: PresencePeriodProcessor::class,
        ),
    ],
)]
final class PresencePeriodOutput
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $startDate,
        public string $endDate,
        public ?string $city,
    ) {
    }
}
