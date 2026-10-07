<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\AssignmentInput;
use App\State\Processor\AssignmentProcessor;
use App\State\Provider\AssignmentsProvider;
use DateTimeImmutable;

/**
 * Responsable interne affecté à un candidat déjà dévoilé par l'entreprise.
 */
#[ApiResource(
    shortName: 'Assignment',
    operations: [
        new Get(
            uriTemplate: '/organizations/{organizationId}/assignments',
            uriVariables: ['organizationId'],
            output: CollectionOutput::class,
            provider: AssignmentsProvider::class,
            paginationEnabled: false,
        ),
        new Put(
            uriTemplate: '/organizations/{organizationId}/assignments/{candidateId}',
            uriVariables: ['organizationId', 'candidateId'],
            input: AssignmentInput::class,
            read: false,
            processor: AssignmentProcessor::class,
        ),
        new Delete(
            uriTemplate: '/organizations/{organizationId}/assignments/{candidateId}',
            uriVariables: ['organizationId', 'candidateId'],
            read: false,
            processor: AssignmentProcessor::class,
        ),
    ],
)]
final class AssignmentOutput
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $candidateId,
        public string $assigneeRecruiterId,
        public string $assignedByRecruiterId,
        public DateTimeImmutable $assignedAt,
    ) {
    }
}
