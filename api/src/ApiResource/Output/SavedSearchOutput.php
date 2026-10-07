<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\SavedSearchInput;
use App\State\Processor\SavedSearchDuplicateProcessor;
use App\State\Processor\SavedSearchProcessor;
use App\State\Provider\SavedSearchesProvider;
use App\State\Provider\SavedSearchProvider;
use DateTimeImmutable;

/**
 * Recherche du cockpit entreprise (task 072). L'auteur est exposé : une recherche partagée est lue
 * par ses collègues, ils doivent savoir de qui elle vient.
 *
 * `newCount` est PROPRE AU RECRUTEUR COURANT — deux membres lisant la même recherche partagée ont
 * des badges indépendants. Ouvrir la recherche (GET item) remet le repère à jour.
 *
 * `id` non-identifiant : pas d'IRI/Location à générer (cf. api-conventions.md).
 */
#[ApiResource(
    shortName: 'Search',
    operations: [
        new GetCollection(
            uriTemplate: '/searches',
            output: CollectionOutput::class,
            provider: SavedSearchesProvider::class,
            paginationEnabled: false,
        ),
        // `requirements` et pas seulement pour la forme : sans elle, `/searches/workspace` serait lu
        // comme une recherche dont l'identifiant vaudrait « workspace », et la route littérale du
        // plan de travail ne serait jamais atteinte. Un identifiant EST un uuid : le dire au routeur
        // règle le conflit une fois pour toutes, là où une priorité se serait défaite au premier
        // ajout de route.
        new Get(
            uriTemplate: '/searches/{id}',
            uriVariables: ['id'],
            requirements: ['id' => '[0-9a-fA-F-]{36}'],
            provider: SavedSearchProvider::class,
        ),
        // Créer et ajuster renvoient la recherche ET son jeu : poser un critère et voir ce qu'il
        // ramène est un seul geste, et l'API vient justement de recalculer ce jeu pour l'écrire.
        new Post(
            uriTemplate: '/searches',
            input: SavedSearchInput::class,
            output: SavedSearchWithResultsOutput::class,
            processor: SavedSearchProcessor::class,
        ),
        new Patch(
            uriTemplate: '/searches/{id}',
            uriVariables: ['id'],
            inputFormats: ['json' => ['application/json']],
            input: SavedSearchInput::class,
            output: SavedSearchWithResultsOutput::class,
            read: false,
            processor: SavedSearchProcessor::class,
        ),
        new Delete(
            uriTemplate: '/searches/{id}',
            uriVariables: ['id'],
            read: false,
            processor: SavedSearchProcessor::class,
        ),
        // Duplication : ouverte à qui peut LIRE la recherche. C'est la porte de sortie du
        // destinataire d'une recherche partagée — il ne peut pas l'éditer, il s'en fait une copie.
        new Post(
            uriTemplate: '/searches/{id}/duplicate',
            uriVariables: ['id'],
            input: false,
            read: false,
            processor: SavedSearchDuplicateProcessor::class,
        ),
    ],
)]
final class SavedSearchOutput
{
    /**
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $criteria,
        public bool $isAutoNamed,
        public bool $isShared,
        public string $ownerId,
        public string $ownerName,
        public bool $isOwn,
        public int $newCount,
        /**
         * Taille du jeu de résultats de la recherche — le « 34 » qu'affiche sa carte.
         *
         * Lue sur l'appartenance matérialisée, la même que celle qui date les entrées de `newCount` :
         * la carte annonce donc exactement le jeu que la Table servira. Vaut 0 tant qu'aucun critère
         * n'est posé — le vivier entier n'est pas un résultat de recherche.
         */
        public int $resultCount,
        /**
         * Où en est le recruteur COURANT dans cette pile — comme `newCount`, et pour la même
         * raison : deux recruteurs sur une recherche partagée n'ont pas le même avancement.
         *
         * BORNÉ au jeu de résultats : le jeu rétrécit quand un profil cesse de correspondre, alors
         * que la ligne de revue survit. Non borné, `seenCount` pourrait dépasser `resultCount`.
         */
        public int $seenCount,
        /**
         * Ce que la recherche a produit : les profils retenus, tous auteurs confondus — en pratique
         * son seul auteur, le partage étant en lecture seule.
         *
         * NON borné au jeu, contrairement à `seenCount` : `/searches/{id}/favorites` sert tous les
         * favoris sans en écarter aucun, et la carte doit annoncer ce que la Table servira.
         */
        public int $favoriteCount,
        /**
         * Nulle tant que la recherche est active. Renseignée, elle dit que son auteur a
         * déclaré le travail terminé : l'écran l'éteint, et l'API refuse de la modifier.
         */
        public ?DateTimeImmutable $closedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
