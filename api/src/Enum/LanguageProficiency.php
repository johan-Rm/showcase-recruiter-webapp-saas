<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Niveau de maîtrise d'une langue, tel que Jarvi/LinkedIn le fournit (task 114).
 *
 * ⚠️ Ce n'est **pas** le CECRL : il n'existe aucune correspondance officielle entre cette échelle et
 * A1–C2, et en inventer une tromperait l'entreprise. On conserve donc l'échelle source telle quelle.
 *
 * `rank()` ordonne l'échelle pour permettre un filtre « niveau minimum ».
 */
enum LanguageProficiency: string
{
    case ELEMENTARY = 'ELEMENTARY';
    case LIMITED_WORKING = 'LIMITED_WORKING';
    case PROFESSIONAL_WORKING = 'PROFESSIONAL_WORKING';
    case FULL_PROFESSIONAL = 'FULL_PROFESSIONAL';
    case NATIVE_OR_BILINGUAL = 'NATIVE_OR_BILINGUAL';

    public function rank(): int
    {
        return match ($this) {
            self::ELEMENTARY => 1,
            self::LIMITED_WORKING => 2,
            self::PROFESSIONAL_WORKING => 3,
            self::FULL_PROFESSIONAL => 4,
            self::NATIVE_OR_BILINGUAL => 5,
        };
    }

    /**
     * Le niveau est ABSENT sur environ un tiers des entrées du vivier : on renvoie alors `null`
     * plutôt qu'un niveau supposé. `PROFESSIONAL` est une variante marginale observée dans les
     * données réelles, ramenée au palier professionnel.
     */
    public static function fromJarvi(mixed $value): ?self
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return 'PROFESSIONAL' === $value
            ? self::PROFESSIONAL_WORKING
            : self::tryFrom($value);
    }

    /**
     * Paliers acceptables pour un filtre « au moins ce niveau ». Comparer des rangs en base
     * demanderait une colonne d'ordre : on énumère plutôt les valeurs, l'échelle tenant en 5 paliers.
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
