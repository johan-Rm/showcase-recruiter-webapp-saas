<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminVivierGapProvider;

/**
 * Le détail d'un manque de qualification (task API-034) : derrière « Ancienneté renseignée
 * 2 208 /2 561 », les profils que le chiffre ne compte pas et la raison de chacun.
 *
 * Complète {@see AdminVivierOutput}, qui compte sans expliquer. Le compteur ne bouge pas pour
 * autant : cette opération éclaire la mesure, elle ne la corrige pas — l'écran et
 * `app:qualification:report` continuent d'annoncer le même nombre.
 *
 * Les motifs voyagent en CODES, pas en phrases : l'interface les nomme dans sa langue, et `api/`
 * reste libre de texte d'affichage.
 *
 * Lecture seule, gardée ROLE_ADMIN.
 */
#[ApiResource(
    shortName: 'AdminVivierGap',
    operations: [
        new Get(
            uriTemplate: '/admin/vivier/gaps/{metric}',
            uriVariables: ['metric'],
            name: 'admin_vivier_gaps',
            provider: AdminVivierGapProvider::class,
        ),
    ],
)]
final class AdminVivierGapOutput
{
    /**
     * `breakdown` porte TOUJOURS le manque entier, jamais restreint par `reason` : sélectionner un
     * motif à l'écran ne doit pas faire bouger les compteurs qu'on vient de lire. Le manque total
     * se lit comme sa somme.
     *
     * `total`, `page` et `perPage` gardent le sens qu'ils ont dans {@see CollectionOutput} : ils
     * décrivent la liste effectivement parcourue, donc filtrée. Deux champs nommés « total » qui
     * compteraient deux choses différentes seraient un piège, pas un service.
     *
     * @param list<array{reason: string, count: int}>                                                                         $breakdown
     * @param list<array{id: string, givenName: string, familyName: string, city: ?string, country: ?string, reason: string}> $items
     */
    public function __construct(
        #[ApiProperty(required: true)]
        public string $metric,
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'reason' => ['type' => 'string'],
                    'count' => ['type' => 'integer'],
                ],
                'required' => ['reason', 'count'],
            ],
        ])]
        public array $breakdown,
        /** Le motif filtré, rendu explicite ; `null` quand la liste porte tout le manque. */
        #[ApiProperty(required: true)]
        public ?string $reason,
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                    'givenName' => ['type' => 'string'],
                    'familyName' => ['type' => 'string'],
                    'city' => ['type' => 'string', 'nullable' => true],
                    'country' => ['type' => 'string', 'nullable' => true],
                    'reason' => ['type' => 'string'],
                ],
                'required' => ['id', 'givenName', 'familyName', 'city', 'country', 'reason'],
            ],
        ])]
        public array $items,
        #[ApiProperty(required: true)]
        public int $total,
        #[ApiProperty(required: true)]
        public int $page,
        #[ApiProperty(required: true)]
        public int $perPage,
    ) {
    }
}
