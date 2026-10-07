<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Un établissement distinct du vivier (task 115), regroupé par NOM normalisé.
 */
final readonly class HarvestedSchool
{
    /**
     * @param string       $name      forme d'affichage retenue (la graphie la plus fréquente)
     * @param int          $frequency formations portant cet établissement
     * @param list<string> $jarviIds  entités école Jarvi rassemblées sous ce nom
     * @param list<string> $variants  graphies brutes rencontrées
     */
    public function __construct(
        public string $key,
        public string $name,
        public int $frequency,
        public array $jarviIds,
        public array $variants,
    ) {
    }
}
