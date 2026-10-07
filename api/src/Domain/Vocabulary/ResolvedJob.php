<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Concept métier reconnu dans un intitulé de poste (task 118). `exact` distingue une égalité de
 * libellé d'une reconnaissance partielle : les deux n'ont pas la même fiabilité et ne doivent pas
 * être confondues par les consommateurs.
 */
final readonly class ResolvedJob
{
    public function __construct(
        public int $conceptId,
        public bool $isStatus,
        public bool $exact,
    ) {
    }
}
