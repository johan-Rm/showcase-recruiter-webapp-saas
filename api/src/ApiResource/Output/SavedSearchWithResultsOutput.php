<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;

/**
 * Ce que renvoient la création et l'ajustement d'une recherche : la recherche ET ce qu'elle ramène.
 *
 * POURQUOI — poser un critère et voir ses résultats est UN geste, pas deux. L'API recalcule déjà le
 * jeu pendant l'écriture (`SearchMembershipRecalculator`) : le client qui rappelait ensuite la fiche
 * puis les résultats redemandait ce que le serveur venait de produire, et payait deux allers-retours
 * pour l'apprendre. Il n'en reste qu'un.
 *
 * Cela ferme aussi un piège d'ordonnancement : servir les résultats remet à jour le repère de lecture
 * du recruteur, donc lire la fiche APRÈS eux rapportait un `newCount` déjà consommé. L'ordre était à
 * la charge de l'appelant ; il est maintenant dans la réponse.
 *
 * Deux sous-objets et non un aplatissement : ce sont deux ressources distinctes, que le client range
 * dans deux endroits distincts. Les fondre obligerait à les redécouper à l'arrivée.
 */
final class SavedSearchWithResultsOutput
{
    /** @param SearchResultsOutput<CandidateCardOutput> $results */
    public function __construct(
        /**
         * EMBARQUÉE et non référencée : `SavedSearchOutput` est une ressource, et le sérialiseur
         * remplace par défaut une ressource imbriquée par son IRI — la réponse portait
         * `"search": "/v1/searches/…"`, c'est-à-dire l'aller-retour qu'on voulait justement éviter.
         */
        #[ApiProperty(readableLink: true)]
        public SavedSearchOutput $search,
        public SearchResultsOutput $results,
    ) {
    }
}
