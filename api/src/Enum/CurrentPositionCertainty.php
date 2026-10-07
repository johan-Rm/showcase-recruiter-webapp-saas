<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Degré de certitude du poste actuel (task 117). Mesuré sur le vivier : la définition « position
 * sans date de fin » ne tranche que **53 %** des profils.
 *
 * Distinguer ABSENT d'AMBIGUOUS est le cœur de la carte : « aucun poste en cours » et « impossible
 * de trancher entre plusieurs » sont deux situations opposées qu'un champ vide confondrait.
 */
enum CurrentPositionCertainty: string
{
    /** Une seule position en cours, ou plusieurs dans la MÊME entreprise (promotion, ligne non clôturée). */
    case CERTAIN = 'CERTAIN';

    /** Plusieurs positions en cours dans des entreprises différentes : la plus récente est exposée, sans garantie. */
    case AMBIGUOUS = 'AMBIGUOUS';

    /** Aucune position en cours — souvent un profil LinkedIn dont la dernière expérience n'a jamais été rouverte. */
    case ABSENT = 'ABSENT';
}
