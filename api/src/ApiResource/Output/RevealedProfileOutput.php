<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\BatchRevealInput;
use App\ApiResource\Input\RevealInput;
use App\State\Processor\BatchRevealProcessor;
use App\State\Processor\RevealProcessor;
use App\State\Provider\RevealedProfilesProvider;
use DateTimeImmutable;

/**
 * Profil dévoilé par une entreprise (espace « déjà dévoilés » partagé). Le POST déclenche le
 * débit d'un crédit (idempotent). `id` non-identifiant : pas d'IRI/Location à générer.
 */
#[ApiResource(
    shortName: 'RevealedProfile',
    operations: [
        new Post(
            uriTemplate: '/organizations/{organizationId}/revealed-profiles',
            uriVariables: ['organizationId'],
            input: RevealInput::class,
            processor: RevealProcessor::class,
        ),
        // Le LOT (task 163) : un seul accès pour tous les profils désignés, tout ou rien, plafonné.
        new Post(
            uriTemplate: '/organizations/{organizationId}/revealed-profiles/batch',
            uriVariables: ['organizationId'],
            input: BatchRevealInput::class,
            output: CollectionOutput::class,
            processor: BatchRevealProcessor::class,
        ),
        new Get(
            uriTemplate: '/organizations/{organizationId}/revealed-profiles',
            uriVariables: ['organizationId'],
            output: CollectionOutput::class,
            provider: RevealedProfilesProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class RevealedProfileOutput
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $candidateId,
        public string $revealedById,
        /** Ce qui a été dévoilé : coordonnées, CV ou compte rendu (cf. RevealType). */
        public string $type,
        public DateTimeImmutable $revealedAt,
    ) {
    }
}
