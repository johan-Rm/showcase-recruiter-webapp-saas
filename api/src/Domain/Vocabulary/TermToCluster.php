<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Un terme récolté à soumettre au clustering : sa forme brute et sa fréquence (nombre de profils
 * distincts). La fréquence sert à prioriser la tête de la distribution et n'est pas réinterprétée
 * par le modèle.
 */
final readonly class TermToCluster
{
    public function __construct(
        public string $term,
        public int $frequency,
    ) {
    }
}
