<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\ConfigProvider;

/**
 * Valeurs métier publiques consommées par le webapp (coût d'un dévoilé, crédits offerts à la
 * création, locales activées + locale par défaut, taille de page, nomenclatures fermées).
 * Cf. api-routes.md /v1/config.
 *
 * Une seule ressource pour tout cela : ces valeurs sont sans intérêt séparément et changent au même
 * rythme — celui d'un déploiement. Les servir en une réponse évite au client autant d'allers-retours
 * qu'il y a de listes, avant même d'avoir affiché quoi que ce soit.
 */
#[ApiResource(
    shortName: 'Config',
    operations: [
        new Get(
            uriTemplate: '/config',
            provider: ConfigProvider::class,
            read: true,
        ),
    ],
)]
final class ConfigOutput
{
    /** @param list<string> $locales */
    public function __construct(
        public int $revealCost,
        public int $welcomeCredits,
        public array $locales,
        public string $defaultLocale,
        public int $perPage,
        /**
         * Combien de profils une recherche donne à travailler, quelle que soit sa taille réelle.
         *
         * Exposée pour que l'écran demande une page de cette taille sans recopier le nombre : c'est
         * exactement ce que cette ressource existe pour éviter.
         */
        public int $searchLoadLimit,
        public ConfigReferentialsOutput $referentials,
    ) {
    }
}
