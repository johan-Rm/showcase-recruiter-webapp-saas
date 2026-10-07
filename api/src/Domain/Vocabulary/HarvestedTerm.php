<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyType;

/**
 * Un terme distinct récolté dans la réplica, avec sa fréquence = nombre de PROFILS distincts qui
 * le portent (jamais le nombre d'occurrences : un même profil ne compte qu'une fois).
 */
final readonly class HarvestedTerm
{
    public function __construct(
        public VocabularyType $type,
        public string $term,
        public int $frequency,
    ) {
    }
}
