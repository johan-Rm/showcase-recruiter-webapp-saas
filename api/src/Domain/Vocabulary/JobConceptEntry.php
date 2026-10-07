<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Un libellé du vocabulaire des postes, avec ce qu'il faut pour arbitrer entre plusieurs
 * correspondances partielles (task 118).
 */
final readonly class JobConceptEntry
{
    public int $words;

    public function __construct(
        public string $term,
        public int $conceptId,
        /** Profils portant ce libellé : un terme RARE est plus discriminant qu'un terme courant. */
        public int $frequency,
        /** « Stage / Internship », « Étudiant »… — une situation, pas un métier. */
        public bool $isStatus,
    ) {
        $this->words = \count(explode(' ', $term));
    }
}
