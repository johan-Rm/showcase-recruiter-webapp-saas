<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Niveau d'études, DÉRIVÉ des titres de formation à l'ingestion (task 112) — troisième et dernière
 * « qualification portée implicitement » du cadrage, avec la séniorité et le secteur.
 *
 * Nomenclature fermée de 6 valeurs, reprise du cadrage. `rank()` sert à retenir le niveau le PLUS
 * ÉLEVÉ d'un parcours (et non le plus récent). Master et ingénieur sont **à égalité** : tous deux
 * bac+5, aucun ne prime — à rang égal, le premier rencontré est conservé.
 */
enum EducationLevel: string
{
    case BAC = 'BAC';
    case BAC_PLUS_2 = 'BAC_PLUS_2';
    case LICENCE = 'LICENCE';
    case MASTER = 'MASTER';
    case INGENIEUR = 'INGENIEUR';
    case DOCTORAT = 'DOCTORAT';

    public function rank(): int
    {
        return match ($this) {
            self::BAC => 1,
            self::BAC_PLUS_2 => 2,
            self::LICENCE => 3,
            self::MASTER, self::INGENIEUR => 4,
            self::DOCTORAT => 5,
        };
    }

    /**
     * Niveaux d'un rang supérieur ou égal — pour un filtre « au moins ce niveau ». Master et
     * ingénieur partageant le rang 4, « au moins master » retient les deux (plus le doctorat).
     *
     * @return list<self>
     */
    public static function atLeast(self $minimum): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $level): bool => $level->rank() >= $minimum->rank(),
        ));
    }
}
