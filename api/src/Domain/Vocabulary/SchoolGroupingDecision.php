<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Nom CANONIQUE attribué à un établissement par le regroupement sémantique (task 115). `key`
 * référence la {@see HarvestedSchool} d'origine ; plusieurs clés partageant le même `canonicalName`
 * désignent le même établissement (« ENCG Casablanca » et son nom complet).
 */
final readonly class SchoolGroupingDecision
{
    public function __construct(
        public string $key,
        public string $canonicalName,
    ) {
    }
}
