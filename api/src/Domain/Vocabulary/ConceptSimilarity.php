<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Proximité MESURÉE entre deux concepts distincts (task 111). Paire non ordonnée : `conceptAId`
 * est toujours le plus petit identifiant, la similarité étant symétrique.
 */
final readonly class ConceptSimilarity
{
    public function __construct(
        public int $conceptAId,
        public int $conceptBId,
        /** Nombre de profils portant les DEUX concepts — la preuve lisible derrière le score. */
        public int $cooccurrences,
        public float $jaccard,
        public float $npmi,
    ) {
    }
}
