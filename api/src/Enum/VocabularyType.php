<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Nature d'un concept du vocabulaire : un poste/métier OU une compétence. Deux sacs séparés —
 * un poste n'est pas une compétence, on ne les mélange jamais (cf. dev-book/resources/taxonomie/README.md).
 */
enum VocabularyType: string
{
    case METIER = 'METIER';
    case COMPETENCE = 'COMPETENCE';
}
