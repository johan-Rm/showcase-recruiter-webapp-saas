<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminOverflowingSearchProvider;

/**
 * GET /v1/admin/searches — LES RECHERCHES LARGES, toutes organisations confondues.
 *
 * Une recherche au-dessus du seuil recommandé se parcourt entièrement — rien n'est caché au
 * recruteur —, mais trier plusieurs centaines de profils un par un est un travail que personne ne
 * mène jusqu'au bout. Cet écran dit où ça se produit : soit une recherche trop large qu'on peut
 * aider à resserrer, soit une demande que le vivier couvre largement.
 *
 * Lecture seule et gardée `ROLE_ADMIN`, comme les autres inventaires d'administration : rien ne s'y
 * corrige, on y regarde.
 */
#[ApiResource(
    shortName: 'AdminOverflowingSearch',
    operations: [
        new Get(
            uriTemplate: '/admin/searches',
            output: CollectionOutput::class,
            name: 'admin_overflowing_searches',
            provider: AdminOverflowingSearchProvider::class,
            paginationEnabled: false,
            read: true,
        ),
    ],
)]
final class AdminOverflowingSearchOutput
{
    /** @param list<array{facet: string, value: string, group: string}> $criteria */
    public function __construct(
        public string $id,
        /**
         * Le nom SAISI, souvent vide.
         *
         * Une recherche non renommée n'en a pas : son titre se compose des critères, et cette
         * composition vit côté écran. On sert donc les deux et on laisse l'affichage décider — la
         * recomposer ici en ferait une seconde règle à tenir d'accord avec la première.
         */
        public string $name,
        public array $criteria,
        public string $organizationName,
        public string $ownerName,
        /** Le nombre de correspondances de la recherche. */
        public int $matchCount,
        /**
         * LE SEUIL AU-DESSUS DUQUEL une recherche est dite large — et non plus ce qui est servi.
         *
         * Le jeu se sert désormais entier : plus rien n'est caché au recruteur, et l'écart avec le
         * nombre réel ne mesure plus un manque mais un **dépassement du conseil**. Le nom du champ
         * garde son ancienne promesse ; le corriger casserait l'écran de supervision, qui vit dans
         * une autre lane et devra suivre.
         */
        public int $servedCount,
        public bool $isClosed,
        /** Dernier contact humain : une recherche débordante mais abandonnée n'appelle pas la même lecture. */
        public string $lastActivityAt,
    ) {
    }
}
