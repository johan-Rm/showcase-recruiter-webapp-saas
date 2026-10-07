<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use DateTimeImmutable;
use Exception;

/**
 * Années d'expérience d'un profil, dérivées des `positions` Jarvi.
 *
 * Jarvi ne fournit PAS ce chiffre : il donne les dates de chaque poste. On l'obtient donc à
 * l'ingestion, une fois, dans une colonne normalisée — la Table l'affiche et la recherche le filtre
 * sans jamais rejouer le calcul (cf. tasks 061 / 086).
 *
 * Les périodes qui se chevauchent (cumul d'emploi, missions parallèles) sont FUSIONNÉES : quelqu'un
 * qui tient deux postes la même année a vécu une année, pas deux.
 */
final class ExperienceYearsCalculator
{
    private const int MONTHS_PER_YEAR = 12;

    /**
     * @param list<array<string, mixed>> $positions `positions` Jarvi (title, companyName, startDate, endDate)
     *
     * @return int années pleines ; 0 si aucune période exploitable
     */
    public static function fromPositions(array $positions, ?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();

        $intervals = [];
        foreach ($positions as $position) {
            $start = self::date($position['startDate'] ?? null);
            if (!$start instanceof DateTimeImmutable || $start > $now) {
                continue;   // sans date de début, la période n'est pas mesurable
            }

            // Poste en cours (endDate null) : la période court jusqu'à aujourd'hui.
            $end = self::date($position['endDate'] ?? null) ?? $now;
            if ($end < $start) {
                continue;   // données incohérentes côté source : on ne devine pas
            }

            $intervals[] = [$start, $end > $now ? $now : $end];
        }

        if ([] === $intervals) {
            return 0;
        }

        usort($intervals, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $months = 0;
        [$currentStart, $currentEnd] = $intervals[0];
        foreach (\array_slice($intervals, 1) as [$start, $end]) {
            if ($start <= $currentEnd) {
                $currentEnd = max($currentEnd, $end);   // chevauchement → on étire la période courante
                continue;
            }

            $months += self::months($currentStart, $currentEnd);
            [$currentStart, $currentEnd] = [$start, $end];
        }
        $months += self::months($currentStart, $currentEnd);

        return intdiv($months, self::MONTHS_PER_YEAR);
    }

    private static function date(mixed $value): ?DateTimeImmutable
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    private static function months(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $diff = $start->diff($end);

        return ($diff->y * self::MONTHS_PER_YEAR) + $diff->m;
    }
}
