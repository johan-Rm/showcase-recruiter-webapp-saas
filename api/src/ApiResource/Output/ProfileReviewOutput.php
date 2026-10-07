<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\ProfileReviewInput;
use App\State\Processor\ProfileReviewProcessor;

/**
 * Résultat d'une déclaration de tri en lot : ce que la recherche compte désormais comme vu et comme
 * écarté, POUR LE RECRUTEUR COURANT.
 *
 * On renvoie les totaux plutôt que la liste des lignes touchées : l'écran s'en sert pour afficher un
 * avancement, et il connaît déjà les identifiants qu'il vient d'envoyer.
 */
#[ApiResource(
    shortName: 'ProfileReview',
    operations: [
        new Post(
            uriTemplate: '/searches/{searchId}/reviews',
            uriVariables: ['searchId'],
            input: ProfileReviewInput::class,
            processor: ProfileReviewProcessor::class,
        ),
    ],
)]
final class ProfileReviewOutput
{
    public function __construct(
        /** Profils du jeu que ce recruteur a regardés. */
        public int $seenCount,
        /** Profils qu'il a écartés — ils quittent sa pile, pas celle des autres. */
        public int $dismissedCount,
        /** Taille du jeu, pour situer les deux précédents sans second appel. */
        public int $totalCount,
    ) {
    }
}
