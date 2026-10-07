<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Enum\QualificationGapReason;
use App\Enum\QualificationMetric;
use DateTimeImmutable;
use Exception;

/**
 * Pourquoi une qualification dérivée manque, profil par profil (task API-034).
 *
 * {@see QualificationReporter} sait compter les manques ; il ne sait pas les expliquer, et la cause
 * n'est écrite nulle part en base — les colonnes gardent le résultat (`0`, `null`), jamais la
 * raison. Elle est donc REJOUÉE ici, à la demande, sur les seuls profils déjà identifiés comme en
 * manque. Rien n'est stocké : un motif figé en base deviendrait faux à la première évolution d'un
 * calcul, sans que rien ne le signale.
 *
 * **Chaque branche est le miroir d'une branche du calculateur d'origine.** Les deux se lisent côte
 * à côte, et les tests vérifient l'appariement : pour chaque cas, le calcul rend bien zéro ET le
 * motif attendu tombe. Toucher l'un sans l'autre fait tomber la suite — c'est le seul garde-fou
 * contre une explication qui dérive de ce qu'elle explique.
 *
 * Pur : aucune base, aucune entité, `$now` injectable.
 *
 * @see \App\Domain\Jarvi\ExperienceYearsCalculator
 * @see \App\Domain\Jarvi\CurrentPositionExtractor
 * @see \App\Domain\Jarvi\EducationLevelCalculator
 */
final class QualificationGapClassifier
{
    /**
     * @param list<array<string, mixed>> $positions  `workExperiences` du réplica Jarvi
     * @param list<array<string, mixed>> $educations `educations` du réplica Jarvi
     */
    public static function classify(
        QualificationMetric $metric,
        array $positions,
        array $educations,
        ?DateTimeImmutable $now = null,
    ): QualificationGapReason {
        return match ($metric) {
            QualificationMetric::EXPERIENCE_YEARS => self::forExperienceYears($positions, $now),
            QualificationMetric::CURRENT_ROLE_MONTHS => self::forCurrentRoleMonths($positions, $now),
            QualificationMetric::EDUCATION_LEVEL => self::forEducationLevel($educations),
            QualificationMetric::JARVI_UPDATED_AT => QualificationGapReason::SOURCE_WITHOUT_DATE,
        };
    }

    /**
     * Miroir d'`ExperienceYearsCalculator::fromPositions()`, qui rend `0` par quatre chemins : aucun
     * poste, aucune date de début exploitable, aucune période cohérente, ou un cumul que
     * `intdiv($months, 12)` ramène à zéro.
     *
     * @param list<array<string, mixed>> $positions
     */
    public static function forExperienceYears(array $positions, ?DateTimeImmutable $now = null): QualificationGapReason
    {
        $now ??= new DateTimeImmutable();

        if ([] === $positions) {
            return QualificationGapReason::NO_POSITION;
        }

        $started = 0;
        $measurable = 0;
        foreach ($positions as $position) {
            $start = self::date($position['startDate'] ?? null);
            if (!$start instanceof DateTimeImmutable || $start > $now) {
                continue;
            }
            ++$started;

            // Poste en cours : la période court jusqu'à aujourd'hui, comme dans le calcul.
            if ((self::date($position['endDate'] ?? null) ?? $now) >= $start) {
                ++$measurable;
            }
        }

        return match (true) {
            0 === $started => QualificationGapReason::NO_USABLE_START_DATE,
            0 === $measurable => QualificationGapReason::INCOHERENT_DATES,
            default => QualificationGapReason::UNDER_ONE_YEAR,
        };
    }

    /**
     * Miroir de `CurrentPositionExtractor::fromPositions()` : sans poste ouvert la durée n'existe
     * pas, et avec un poste ouvert `months()` rend `0` soit faute de date de début exploitable,
     * soit parce qu'il n'y a pas encore un mois plein.
     *
     * @param list<array<string, mixed>> $positions
     */
    public static function forCurrentRoleMonths(array $positions, ?DateTimeImmutable $now = null): QualificationGapReason
    {
        $now ??= new DateTimeImmutable();

        $ongoing = array_values(array_filter(
            $positions,
            static fn (array $position): bool => null === ($position['endDate'] ?? null),
        ));
        if ([] === $ongoing) {
            return QualificationGapReason::NO_ONGOING_POSITION;
        }

        $start = self::mostRecentStart($ongoing, $now);

        return !$start instanceof DateTimeImmutable || $start > $now
            ? QualificationGapReason::ONGOING_WITHOUT_START_DATE
            : QualificationGapReason::UNDER_ONE_MONTH;
    }

    /**
     * Miroir d'`EducationLevelCalculator::fromEducations()`, qui rend `null` faute de formation ou
     * faute d'intitulé reconnaissable. Les deux appellent des corrections opposées — enrichir la
     * source, ou enrichir les motifs de reconnaissance.
     *
     * @param list<array<string, mixed>> $educations
     */
    public static function forEducationLevel(array $educations): QualificationGapReason
    {
        return [] === $educations
            ? QualificationGapReason::NO_EDUCATION
            : QualificationGapReason::UNRECOGNIZED_TITLE;
    }

    /**
     * Reprend le départage de `CurrentPositionExtractor::mostRecent()` : la première position ouvre
     * la comparaison quelle que soit sa date, et seule une date exploitable peut la détrôner. La
     * date retenue peut donc rester absente ou future — c'est exactement ce qui fait rendre `0`.
     *
     * @param non-empty-list<array<string, mixed>> $ongoing
     */
    private static function mostRecentStart(array $ongoing, DateTimeImmutable $now): ?DateTimeImmutable
    {
        $bestStart = self::date($ongoing[0]['startDate'] ?? null);
        foreach (\array_slice($ongoing, 1) as $position) {
            $start = self::date($position['startDate'] ?? null);
            if (!$start instanceof DateTimeImmutable || $start > $now) {
                continue;
            }
            if (!$bestStart instanceof DateTimeImmutable || $start > $bestStart) {
                $bestStart = $start;
            }
        }

        return $bestStart;
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
