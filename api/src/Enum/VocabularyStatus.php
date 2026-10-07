<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Cycle de vie d'un concept ou d'un libellé du vocabulaire. `CANDIDAT` = proposé (récolte ou
 * enrichissement), non encore relu. `VALIDE` = confirmé. `DEPRECIE` = retiré sans suppression.
 */
enum VocabularyStatus: string
{
    case CANDIDAT = 'CANDIDAT';
    case VALIDE = 'VALIDE';
    case DEPRECIE = 'DEPRECIE';
}
