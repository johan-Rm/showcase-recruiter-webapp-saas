<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminActivityProvider;
use DateTimeImmutable;

/**
 * GET /v1/admin/activity — LE FIL DES VISITES DE PROFIL, toutes organisations confondues.
 *
 * Visiter n'est pas dévoiler : un recruteur ouvre des fiches masquées sans dépenser de crédit. Un
 * candidat regardé dix fois et jamais dévoilé raconte une fiche qui accroche sans convaincre —
 * c'est cet écart que le fil rend lisible, et c'est pourquoi chaque ligne porte `revealed`.
 *
 * Lecture seule et gardée `ROLE_ADMIN`, comme les autres inventaires d'administration.
 */
#[ApiResource(
    shortName: 'AdminActivity',
    operations: [
        new Get(
            uriTemplate: '/admin/activity',
            output: CollectionOutput::class,
            name: 'admin_activity',
            provider: AdminActivityProvider::class,
            paginationEnabled: false,
            read: true,
        ),
    ],
)]
final class AdminActivityOutput
{
    public function __construct(
        public string $id,
        public DateTimeImmutable $viewedAt,
        public string $recruiterName,
        public ?string $recruiterEmail,
        public string $organizationName,
        public string $candidateId,
        /** Nom complet : l'administration est l'opérateur de la plateforme, elle voit non masqué. */
        public string $candidateName,
        /** Cette organisation a-t-elle dévoilé ce candidat, à un moment quelconque ? */
        public bool $revealed,
    ) {
    }
}
