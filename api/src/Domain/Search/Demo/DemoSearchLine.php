<?php

declare(strict_types=1);

namespace App\Domain\Search\Demo;

/**
 * Ce qu'une recherche de démonstration est devenue en base — task API-029. La commande en fait une
 * ligne de tableau : c'est la seule preuve lisible qu'elle a produit une grille utilisable.
 */
final readonly class DemoSearchLine
{
    public function __construct(
        public string $label,
        public int $resultCount,
        public int $newCount,
        public int $seenCount,
        public int $favoriteCount,
        /** « créée » ou « déjà présente ». */
        public string $state,
        public bool $closed,
        public bool $shared,
        public bool $byColleague,
    ) {
    }
}
