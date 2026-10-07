<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Domain\Vocabulary\CompanyKey;
use App\Enum\Sector;
use DateTimeImmutable;
use Exception;

/**
 * Expérience cumulée PAR SECTEUR (en mois), dérivée des `positions` Jarvi et du référentiel
 * entreprise → secteur (task 095 / 107).
 *
 * Comme {@see ExperienceYearsCalculator}, les périodes qui se chevauchent DANS UN MÊME secteur sont
 * fusionnées : deux postes menés la même année dans l'informatique font une année d'informatique,
 * pas deux. En revanche deux secteurs distincts comptent chacun leur temps — un profil peut avoir
 * 3 ans d'industrie ET 3 ans de conseil sur la même période (cumul d'emploi).
 *
 * Un poste dont l'entreprise n'est pas classée est simplement ignoré : pas de secteur par défaut,
 * qui donnerait une statistique fausse.
 */
final class SectorExperienceCalculator
{
    private const int MONTHS_PER_YEAR = 12;

    /**
     * @param list<array<string, mixed>> $positions          `positions` Jarvi
     * @param array<string, Sector>      $sectorByCompanyKey référentiel entreprise → secteur
     *
     * @return array<string, int> mois cumulés par secteur (valeur d'enum → mois), secteurs sans durée exclus
     */
    public static function fromPositions(array $positions, array $sectorByCompanyKey, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        /** @var array<string, list<array{DateTimeImmutable, DateTimeImmutable}>> $intervalsBySector */
        $intervalsBySector = [];
        foreach ($positions as $position) {
            $key = CompanyKey::fromPosition($position);
            $sector = null !== $key ? ($sectorByCompanyKey[$key] ?? null) : null;
            if (!$sector instanceof Sector) {
                continue;   // entreprise non classée : on n'invente pas de secteur
            }

            $start = self::date($position['startDate'] ?? null);
            if (!$start instanceof DateTimeImmutable || $start > $now) {
                continue;
            }
            $end = self::date($position['endDate'] ?? null) ?? $now;
            if ($end < $start) {
                continue;   // données incohérentes côté source
            }

            $intervalsBySector[$sector->value][] = [$start, $end > $now ? $now : $end];
        }

        $months = [];
        foreach ($intervalsBySector as $sector => $intervals) {
            $total = self::mergedMonths($intervals);
            if ($total > 0) {
                $months[$sector] = $total;
            }
        }

        return $months;
    }

    /**
     * @param list<array{DateTimeImmutable, DateTimeImmutable}> $intervals
     */
    private static function mergedMonths(array $intervals): int
    {
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

        return $months + self::months($currentStart, $currentEnd);
    }

    private static function months(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $diff = $start->diff($end);

        return ($diff->y * self::MONTHS_PER_YEAR) + $diff->m;
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
}
