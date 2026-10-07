<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce qu'un recruteur déclare sur des profils d'une recherche, en lot (task API-007).
 *
 * Trois actions et non deux états : « vu » et « écarté » ne s'excluent pas — un profil écarté a
 * forcément été regardé — et il faut pouvoir défaire un écart sans effacer le fait de l'avoir vu.
 */
enum ProfileReviewAction: string
{
    /** Regardé. Le seuil qui le déclenche vit à l'écran, pas ici. */
    case SEEN = 'SEEN';

    /** Écarté : le profil quitte la pile de tri de CE recruteur, sur CETTE recherche. */
    case DISMISS = 'DISMISS';

    /** Remis dans la pile. Écarter est réversible : sans cela on n'oserait pas s'en servir. */
    case RESTORE = 'RESTORE';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $action): string => $action->value, self::cases());
    }
}
