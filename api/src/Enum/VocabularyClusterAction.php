<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Décision de rattachement d'un terme brut proposée par l'enrichissement : rejoindre un concept
 * existant comme synonyme, fonder un concept, ou écarter le terme comme bruit (fragment de phrase,
 * doublon cassé). Le LLM propose ; tout atterrit en statut CANDIDAT, la validation reste humaine.
 */
enum VocabularyClusterAction: string
{
    case ATTACH = 'ATTACH';
    case NEW = 'NEW';
    case NOISE = 'NOISE';
}
