<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Domain\Vocabulary\ResolvedJob;
use App\Domain\Vocabulary\TermNormalizer;
use App\Domain\Vocabulary\TitleConceptResolver;
use DateTimeImmutable;
use Exception;

/**
 * Expérience cumulée PAR MÉTIER (en mois), dérivée des `positions` Jarvi et du vocabulaire des
 * postes (task 095).
 *
 * Même règle de fusion que {@see SectorExperienceCalculator} : deux postes du même métier menés en
 * parallèle font une seule période, deux métiers distincts comptent chacun leur temps.
 *
 * ⚠️ **La durée sous-estime, par construction.** Le rattachement se fait par égalité exacte de
 * l'intitulé canonique : mesuré sur le vivier, seule la MOITIÉ du parcours d'un candidat couvert est
 * rattachée (médiane 33 %). Le nombre de postes ayant alimenté chaque ligne est donc conservé, pour
 * que l'approximation soit visible plutôt que devinée. Le signal fiable est la **présence** du
 * métier — l'erreur est en moins, jamais en trop.
 */
final class JobExperienceCalculator
{
    private const int MONTHS_PER_YEAR = 12;

    /**
     * @param list<array<string, mixed>> $positions `positions` Jarvi
     *
     * @return array<int, array{months: int, positions: int, exactPositions: int}> par identifiant de concept
     */
    public static function fromPositions(
        array $positions,
        TitleConceptResolver $resolver,
        ?DateTimeImmutable $now = null,
    ): array {
        $now ??= new DateTimeImmutable();
        $normalizer = new TermNormalizer();

        /** @var array<int, list<array{DateTimeImmutable, DateTimeImmutable}>> $intervalsByConcept */
        $intervalsByConcept = [];
        /** @var array<int, int> $exactByConcept */
        $exactByConcept = [];
        foreach ($positions as $position) {
            $title = $normalizer->canonicalizeTitle((string) ($position['title'] ?? ''));
            $job = $resolver->resolve($title);
            if (!$job instanceof ResolvedJob || $job->isStatus) {
                continue;   // hors dictionnaire, ou simple statut : on n'invente pas de métier
            }
            $conceptId = $job->conceptId;

            $start = self::date($position['startDate'] ?? null);
            if (!$start instanceof DateTimeImmutable || $start > $now) {
                continue;
            }
            $end = self::date($position['endDate'] ?? null) ?? $now;
            if ($end < $start) {
                continue;   // données incohérentes côté source
            }

            $intervalsByConcept[$conceptId][] = [$start, $end > $now ? $now : $end];
            $exactByConcept[$conceptId] = ($exactByConcept[$conceptId] ?? 0) + ($job->exact ? 1 : 0);
        }

        $experiences = [];
        foreach ($intervalsByConcept as $conceptId => $intervals) {
            $experiences[$conceptId] = [
                'months' => self::mergedMonths($intervals),
                'positions' => \count($intervals),
                'exactPositions' => $exactByConcept[$conceptId] ?? 0,
            ];
        }

        return $experiences;
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
