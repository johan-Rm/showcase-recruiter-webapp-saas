<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\SearchWorkspaceProvider;

/**
 * GET /v1/searches/workspace — L'ÉTAT DE TRAVAIL du recruteur, en un appel.
 *
 * Ses recherches **ouvertes**, et pour chacune ce qu'elle contient et ce qu'il en a fait. **Rien
 * d'autre** : ni fiche, ni explication de correspondance.
 *
 * C'est ce dépouillement qui fait la route. Mesuré sur un compte à 55 recherches ouvertes, la version
 * qui transportait les fiches et les explications pesait 3 méga-octets, dont 4 % seulement
 * décrivaient l'état du travail. Un identifiant coûte 39 octets, une fiche 1 200, une explication
 * 600 — les mélanger obligeait à payer le plus cher pour obtenir le moins cher. Les fiches se
 * demandent maintenant par lot, les explications par page, chacune à son rythme.
 *
 * **C'est une photo.** Un collègue qui retient un profil pendant la session ne se verra pas dans un
 * état déjà chargé — exactement la fraîcheur d'aujourd'hui pour une recherche ouverte, ni plus ni
 * moins. La route ne dégrade rien, elle ne promet rien non plus.
 *
 * **Aucune pagination, aucune borne** : la clôture automatique des recherches sans contact
 * (`API-049`) borne le nombre de recherches, et elle s'explique à un utilisateur — un plafond posé
 * ici serait un nombre magique invisible. Les listes, elles, décrivent chaque recherche entière.
 */
#[ApiResource(
    shortName: 'SearchWorkspace',
    operations: [
        new Get(
            // Littérale AVANT `/searches/{id}` : sans cette priorité, « workspace » serait lu comme
            // un identifiant de recherche, et la route répondrait 404.
            uriTemplate: '/searches/workspace',
            priority: 10,
            provider: SearchWorkspaceProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class SearchWorkspaceOutput
{
    /** @param list<SearchWorkspaceEntryOutput> $searches les recherches ouvertes, ordre de la collection */
    public function __construct(
        public array $searches,
    ) {
    }
}
