<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminVivierProvider;

/**
 * Santé du vivier (tâche 119) : ce que la base CONTIENT, à distinguer du dashboard `/` qui montre
 * l'activité (`102`) et de `/jarvi` qui supervise les runs (`101`).
 *
 * `snapshot` reprend au mot près la sortie de `app:qualification:report` — l'écran et le CLI
 * doivent donner les mêmes nombres, sinon l'un des deux ment. `distributions` ajoute les têtes de
 * chaque axe : savoir « Informatique : 265 » avant de lancer une recherche. `fillRate` donne la
 * couverture par champ (`filled`/`total`) : la part du vivier pour laquelle on détient réellement
 * chaque coordonnée — mêmes chiffres que le rapport de remplissage, réutilisables hors admin.
 *
 * Lecture seule, gardée ROLE_ADMIN. L'endpoint ne suppose pas l'espace admin — les mêmes chiffres
 * serviront un bloc côté recruteur, seule la surface diffère.
 */
#[ApiResource(
    shortName: 'AdminVivier',
    operations: [
        new Get(
            uriTemplate: '/admin/vivier',
            name: 'admin_vivier',
            provider: AdminVivierProvider::class,
        ),
    ],
)]
final class AdminVivierOutput
{
    /**
     * Le PHPDoc `array<string, …>` seul produit un contrat inexploitable : API Platform ne sait pas
     * rendre une map à clé chaîne et retombe sur `(string|null)[]`, illisible côté front. On force
     * donc le schéma OpenAPI — objets à clés dynamiques (sections du rapport, axes) — et on marque
     * les champs requis : ils sont toujours présents, le type généré ne doit pas les rendre optionnels.
     *
     * @param array<string, array<string, int>>                     $snapshot
     * @param array<string, list<array{label: string, count: int}>> $distributions
     * @param list<array{key: string, filled: int, total: int}>     $fillRate
     */
    public function __construct(
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'object',
            'additionalProperties' => [
                'type' => 'object',
                'additionalProperties' => ['type' => 'integer'],
            ],
        ])]
        public array $snapshot,
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'object',
            'additionalProperties' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string'],
                        'count' => ['type' => 'integer'],
                    ],
                    'required' => ['label', 'count'],
                ],
            ],
        ])]
        public array $distributions,
        #[ApiProperty(required: true, openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'key' => ['type' => 'string'],
                    'filled' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                ],
                'required' => ['key', 'filled', 'total'],
            ],
        ])]
        public array $fillRate,
        #[ApiProperty(required: true)]
        public string $generatedAt,
    ) {
    }
}
