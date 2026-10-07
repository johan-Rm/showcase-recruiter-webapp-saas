<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce sur quoi on demande des propositions. Deux champs, deux sources — cf. `SuggestionFinder`.
 */
enum SuggestionType: string
{
    /** Intitulé de poste : le dictionnaire de concepts, qui porte l'élargissement. */
    case JOB = 'JOB';

    /** Mots-clés : les compétences réellement portées par les profils. */
    case SKILL = 'SKILL';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
