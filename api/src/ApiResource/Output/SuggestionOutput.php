<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\SuggestionKind;
use App\State\Provider\SuggestionProvider;

/**
 * Une proposition d'auto-complétion : le terme, et d'où il vient.
 *
 * ELLE PORTAIT LE NOMBRE DE PROFILS, et ne le porte plus (task API-025). Ce nombre décrivait le
 * vivier avant qu'on l'ait acheté : en tapant quelques lettres au hasard, un recruteur relevait la
 * taille de chaque population et la composition du portefeuille sans avoir dévoilé un seul profil,
 * donc sans dépenser un crédit. Le retirer de l'écran ne suffisait pas — une réponse HTTP se lit
 * dans les outils de développement du navigateur.
 *
 * IL N'A PAS DISPARU POUR AUTANT : il reste calculé en base, où il classe les propositions de la
 * plus portée à la moins portée. L'ordre servi en est la seule trace visible.
 *
 * Aucune proposition ne ramène zéro : elles sont tirées de ce que les profils portent réellement, ce
 * qui rend l'erreur impossible par construction plutôt qu'improbable.
 */
#[ApiResource(
    shortName: 'Suggestion',
    operations: [
        new GetCollection(
            uriTemplate: '/suggestions',
            output: CollectionOutput::class,
            provider: SuggestionProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class SuggestionOutput
{
    public function __construct(
        /** Le terme à poser en critère, tel quel. */
        public string $value,
        /**
         * Ce que ce terme est, et donc ce que la recherche en fera (task API-037) : un métier du
         * dictionnaire élargit aux synonymes, un intitulé écrit ne trouve que lui-même. Le front
         * s'en sert pour séparer les deux à l'écran ; sans cette clé, il ne peut pas prévenir.
         */
        public SuggestionKind $kind = SuggestionKind::CONCEPT,
    ) {
    }
}
