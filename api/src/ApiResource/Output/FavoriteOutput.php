<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\FavoriteInput;
use App\State\Processor\FavoriteProcessor;
use App\State\Provider\FavoritesProvider;
use DateTimeImmutable;

/**
 * Favori d'une recherche (task 073) — le filet large de la Table : il accepte les profils MASQUÉS.
 * Ajout idempotent (unicité search/candidate), suppression par candidateId.
 *
 * Écriture réservée à l'AUTEUR de la recherche : une recherche partagée est en lecture seule.
 * `id` non-identifiant : pas d'IRI/Location à générer.
 */
#[ApiResource(
    shortName: 'Favorite',
    operations: [
        new Post(
            uriTemplate: '/searches/{searchId}/favorites',
            uriVariables: ['searchId'],
            input: FavoriteInput::class,
            processor: FavoriteProcessor::class,
        ),
        new Get(
            uriTemplate: '/searches/{searchId}/favorites',
            uriVariables: ['searchId'],
            output: CollectionOutput::class,
            provider: FavoritesProvider::class,
            paginationEnabled: false,
        ),
        new Delete(
            uriTemplate: '/searches/{searchId}/favorites/{candidateId}',
            uriVariables: ['searchId', 'candidateId'],
            read: false,
            processor: FavoriteProcessor::class,
        ),
    ],
)]
final class FavoriteOutput
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $candidateId,
        public string $addedById,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
