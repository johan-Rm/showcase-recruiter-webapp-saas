<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\SearchExplanationsProvider;

/**
 * GET /v1/searches/{searchId}/explanations?page=&perPage= — LES EXPLICATIONS, page par page.
 *
 * Elles disent pourquoi chaque profil répond à la recherche : le critère cherché, le libellé du
 * profil qui le satisfait — souvent différent, le dictionnaire de métiers ayant fait le
 * rapprochement — et l'endroit où il a été vu.
 *
 * **PAR PAGE, ET NON PAR RECHERCHE ENTIÈRE.** Le poids suit le nombre de critères : mesuré à
 * 5 kilo-octets pour trente profils sur une recherche à un critère, il approche les 18 sur une
 * recherche fournie — et une recherche de cinq cent vingt-cinq profils dépasserait les trois cents.
 * Et la Visionneuse n'affiche qu'un profil à la fois : charger l'explication du cinq centième avant
 * d'avoir vu le premier n'a aucun sens.
 *
 * **L'ordre est celui du jeu**, le même que sert `/searches/workspace` : deux ordres différents
 * rendraient ces pages inutilisables, l'appelant ne sachant plus à quels profils elles répondent.
 *
 * Ni fiche, ni appartenance : les unes viennent de `/searches/profiles`, l'autre de
 * `/searches/workspace`. Trois routes pour trois natures de donnée, chacune avec son poids et son
 * rythme de chargement propre.
 */
#[ApiResource(
    shortName: 'SearchExplanations',
    operations: [
        new Get(
            uriTemplate: '/searches/{searchId}/explanations',
            provider: SearchExplanationsProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class SearchExplanationsOutput
{
    /**
     * @param list<SearchExplanationOutput> $items   les profils EXPLIQUÉS de la page ; un profil qu'aucun critère n'explique n'y figure pas
     * @param int                           $total   la taille du jeu ENTIER, de quoi savoir combien de pages restent
     * @param int                           $page    la page demandée, à partir de 1
     * @param int                           $perPage la taille de page appliquée, bornée par le serveur
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
