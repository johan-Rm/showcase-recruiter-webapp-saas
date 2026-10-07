<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * UNE recherche du plan de travail : ce qu'elle contient, et ce que le recruteur en a fait.
 *
 * Que des identifiants — ni fiche, ni explication. C'est ce qui rend cette réponse quasi gratuite :
 * un identifiant coûte 39 octets, une fiche 1 200, une explication 600. Mélangées, on payait le plus
 * cher pour obtenir le moins cher.
 *
 * **LES CINQ LISTES DÉCRIVENT LA RECHERCHE ENTIÈRE**, jamais une page ni ce qui est chargé. C'est la
 * raison d'être de cette route : un compte déduit d'un chargement partiel vaut « parmi ce que j'ai »
 * et non « dans cette recherche », et il devient faux dès que le client ne charge qu'une tranche.
 */
final class SearchWorkspaceEntryOutput
{
    /**
     * @param list<string> $resultIds    le jeu entier, dans l'ordre du serveur
     * @param list<string> $favoriteIds  les retenus, écartés déduits, dans l'ordre du jeu
     * @param list<string> $dismissedIds les écartés du lecteur — hors de `resultIds`
     * @param list<string> $seenIds      les profils vus par le lecteur
     * @param list<string> $newIds       les profils récemment arrivés — un fait daté, partagé par
     *                                   toute l'organisation
     */
    public function __construct(
        public string $id,
        public array $resultIds,
        public array $favoriteIds,
        public array $dismissedIds,
        public array $seenIds,
        public array $newIds,
    ) {
    }
}
