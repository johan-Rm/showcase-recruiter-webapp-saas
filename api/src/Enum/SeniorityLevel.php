<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Séniorité dérivée de l'ancienneté totale (années d'expérience), à l'ingestion. Seuils produit :
 * junior < 2 ans, confirmé 2–7 ans, senior > 7 ans. Dérivée d'`experienceYears`, pas de l'intitulé
 * (une piste « lue dans le titre » existe côté cadrage — cf. task 095).
 */
enum SeniorityLevel: string
{
    case JUNIOR = 'JUNIOR';
    case CONFIRME = 'CONFIRME';
    case SENIOR = 'SENIOR';

    public static function fromYears(int $years): self
    {
        return match (true) {
            $years < 2 => self::JUNIOR,
            $years <= 7 => self::CONFIRME,
            default => self::SENIOR,
        };
    }
}
