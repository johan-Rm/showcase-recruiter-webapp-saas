<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\SearchProfilesProvider;

/**
 * GET /v1/searches/profiles?ids=…&take=… — LES FICHES, par lot, depuis des recherches nommées.
 *
 * L'appelant nomme des **recherches**, pas des profils : c'est le serveur qui sait lesquels viennent
 * en premier, et lui seul peut dédoublonner l'ensemble. Un profil qui sert trois des recherches
 * demandées n'est sérialisé qu'une fois.
 *
 * **Des identifiants de recherche et non de profil**, parce que l'adresse doit tenir : cent
 * identifiants de profil font 3 700 caractères, six recherches nommées en font 249, et le plafond
 * usuel des intermédiaires est de 8 000.
 *
 * **Les fiches, et rien d'autre.** Ni ordre, ni appartenance, ni explication : l'appelant tient déjà
 * l'ordre, servi par `/searches/workspace`. C'est ce qui permet de doser le chargement — demander
 * trente profils de chacune des recherches affichées, puis compléter au repos.
 *
 * Une lecture, donc une requête de lecture : elle se relance sans risque, se reconnaît à son adresse
 * quand elle est déjà en vol, et reste utilisable par les mécanismes de préchargement du navigateur.
 */
#[ApiResource(
    shortName: 'SearchProfiles',
    operations: [
        new Get(
            // Littérale AVANT `/searches/{id}` : sans cette priorité, « profiles » serait lu comme un
            // identifiant de recherche, et la route répondrait 404.
            uriTemplate: '/searches/profiles',
            priority: 10,
            provider: SearchProfilesProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class SearchProfilesOutput
{
    /** @param list<CandidateCardOutput> $items les fiches, dédoublonnées entre les recherches demandées */
    public function __construct(
        public array $items,
    ) {
    }
}
