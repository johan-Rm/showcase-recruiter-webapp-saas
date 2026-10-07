<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * Le jeu de résultats d'une recherche : une PAGE de cartes, et la liste COMPLÈTE des identifiants.
 *
 * Pourquoi les deux — parce que ce sont deux besoins différents et qu'ils n'ont pas le même poids.
 * Les cartes servent à peindre un écran : on n'en affiche jamais plus d'une page. Les identifiants
 * servent à raisonner sur le jeu ENTIER — compter, cocher une sélection qui survit au filtrage,
 * savoir ce qui reste à charger. Une carte pèse ~700 octets, un identifiant 36.
 *
 * Sans `resultIds`, un client qui veut connaître son jeu doit le télécharger en entier ; avec, il le
 * connaît d'emblée et charge les cartes quand il en a besoin.
 *
 * `resultIds` ignore la pagination et ne connaît aucune borne : il porte le jeu ENTIER, dans l'ordre
 * du serveur. `total` en donne la taille — les deux se correspondent toujours, et c'est ce qui permet
 * à un client de savoir où il va avant d'y arriver.
 *
 * **L'ÉTAT DE TRAVAIL DE LA RECHERCHE voyage avec l'ouverture** — retenus, écartés, vus. Ces trois
 * listes sont servies avec la PREMIÈRE page de la pile, et là seulement : le client demande toujours
 * la page 1 en ouvrant, il reçoit donc l'état complet à ce moment-là et n'a plus rien à demander.
 * Elles portent sur la recherche ENTIÈRE, pas sur la page ni sur les cent servis : c'est tout leur
 * intérêt — un profil retenu au-delà des cartes chargées serait sinon compté sans être connu.
 *
 * **Absentes plutôt que vides** au-delà de la première page : `favoriteIds: []` y signifierait
 * « aucun retenu », ce qui serait faux. L'absence dit « cette réponse ne parle pas de ça » — c'est la
 * seule forme qui ne mente pas. `resultIds` suit la même règle, pour la même raison qu'il n'était
 * déjà relu par personne au-delà de la première page.
 *
 * Enveloppe distincte de `CollectionOutput` et non un champ ajouté à celle-ci : elle est partagée par
 * une quinzaine de collections que cette liste ne concerne pas.
 *
 * @template TItem
 */
final class SearchResultsOutput
{
    /**
     * @param list<TItem>       $items        la page demandée
     * @param int               $total        le nombre RÉEL de correspondances, borne non comprise
     * @param list<string>|null $resultIds    les candidats SERVIS, dans l'ordre — borne comprise ; `null` au-delà de la première page
     * @param list<string>|null $favoriteIds  les retenus de la recherche, écartés déduits
     * @param list<string>|null $dismissedIds les écartés du lecteur — ils ne sont PAS dans `resultIds`
     * @param list<string>|null $seenIds      les profils vus par le lecteur
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        public ?array $resultIds,
        public ?array $favoriteIds = null,
        public ?array $dismissedIds = null,
        public ?array $seenIds = null,
    ) {
    }
}
