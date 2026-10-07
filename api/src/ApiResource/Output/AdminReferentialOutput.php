<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminReferentialProvider;

/**
 * Référentiels qualifiés, pour la relecture humaine (tâche 120).
 *
 * Une page de concepts (métiers ou compétences) triée par fréquence décroissante, avec leurs
 * libellés — **valider un concept sans voir ses libellés ne valide presque rien**, puisque c'est
 * l'assemblage de synonymes qui est jugé.
 *
 * `cumulativeCoverage` répond à la seule question qui pilote l'effort : « si je valide jusqu'ici,
 * quelle part des occurrences du vivier est couverte ? ». C'est ce qui permet de s'arrêter quand le
 * rendement tombe, plutôt que de viser l'exhaustivité.
 *
 * Lecture seule, gardée ROLE_ADMIN.
 */
#[ApiResource(
    shortName: 'AdminReferential',
    operations: [
        new Get(
            uriTemplate: '/admin/referentials/{type}',
            name: 'admin_referentials',
            provider: AdminReferentialProvider::class,
        ),
    ],
)]
final class AdminReferentialOutput
{
    /**
     * `items` et `countsByStatus` portent un schéma OpenAPI explicite : le PHPDoc `list<array{…}>`
     * et `array<string, int>` seuls retombent sur `(string|null)[]`, un contrat que le front ne peut
     * pas typer. On décrit donc la vraie forme et on marque les champs requis (toujours présents).
     *
     * @param list<array{code: ?string, label: string, status: string, frequency: int, labels: list<array{uuid: string, label: string, lang: string, status: string, frequency: int}>, cumulativeCoverage: float}> $items
     * @param array<string, int>                                                                                                                                                                                   $countsByStatus
     */
    public function __construct(
        public string $type,
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'code' => ['type' => ['string', 'null']],
                    'label' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'frequency' => ['type' => 'integer'],
                    'labels' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'uuid' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'lang' => ['type' => 'string'],
                                'status' => ['type' => 'string'],
                                'frequency' => ['type' => 'integer'],
                            ],
                            'required' => ['uuid', 'label', 'lang', 'status', 'frequency'],
                        ],
                    ],
                    'cumulativeCoverage' => ['type' => 'number'],
                ],
                'required' => ['code', 'label', 'status', 'frequency', 'labels', 'cumulativeCoverage'],
            ],
        ])]
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        /** Répartition CANDIDAT / VALIDE / DEPRECIE sur l'ensemble du type, pas sur la page. */
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'object',
            'additionalProperties' => ['type' => 'integer'],
        ])]
        public array $countsByStatus,
        /** Occurrences totales du type — dénominateur de `cumulativeCoverage`. */
        public int $totalFrequency,
    ) {
    }
}
