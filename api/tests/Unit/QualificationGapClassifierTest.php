<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\CurrentPositionExtractor;
use App\Domain\Jarvi\EducationLevelCalculator;
use App\Domain\Jarvi\ExperienceYearsCalculator;
use App\Domain\Reporting\QualificationGapClassifier;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Enum\QualificationGapReason;
use App\Enum\QualificationMetric;
use App\Tests\Support\InMemoryJobConceptCatalog;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Le classement des manques doit expliquer EXACTEMENT ce que les calculateurs ont fait.
 *
 * Chaque cas vérifie donc les deux moitiés : le calcul d'origine rend bien zéro (ou null) sur cette
 * entrée, ET le motif attendu tombe. Toucher l'un sans l'autre fait échouer la suite — c'est le
 * seul garde-fou contre une explication qui dérive de ce qu'elle explique.
 *
 * Le cas qui compte le plus est `UNDER_ONE_YEAR` : un cumul de onze mois rend zéro année, et ce
 * profil-là n'a AUCUN défaut. Sans lui, le compteur du manque surestime le travail à faire.
 */
final class QualificationGapClassifierTest extends TestCase
{
    private const string TODAY = '2026-07-13';

    /**
     * @param list<array<string, mixed>> $positions
     */
    #[DataProvider('experienceProvider')]
    public function testExperienceYears(array $positions, QualificationGapReason $expected): void
    {
        $now = new DateTimeImmutable(self::TODAY);

        self::assertSame(
            0,
            ExperienceYearsCalculator::fromPositions($positions, $now),
            'le cas de test doit bien être un manque, sinon le motif explique autre chose',
        );
        self::assertSame($expected, QualificationGapClassifier::forExperienceYears($positions, $now));
        self::assertContains($expected, QualificationMetric::EXPERIENCE_YEARS->reasons());
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, QualificationGapReason}>
     */
    public static function experienceProvider(): iterable
    {
        yield 'aucun poste' => [[], QualificationGapReason::NO_POSITION];

        yield 'postes sans date de début' => [
            [self::position(null, null), self::position(null, '2020-01-01')],
            QualificationGapReason::NO_USABLE_START_DATE,
        ];

        yield 'date de début illisible' => [
            [self::position('pas une date', null)],
            QualificationGapReason::NO_USABLE_START_DATE,
        ];

        yield 'date de début dans le futur' => [
            [self::position('2030-01-01', null)],
            QualificationGapReason::NO_USABLE_START_DATE,
        ];

        yield 'fin avant le début' => [
            [self::position('2026-04-01', '2026-01-31')],
            QualificationGapReason::INCOHERENT_DATES,
        ];

        yield 'dix mois : mesurable, mais pas une année' => [
            [self::position('2025-09-01', '2026-07-01')],
            QualificationGapReason::UNDER_ONE_YEAR,
        ];

        yield 'deux postes menés en parallèle, fusionnés sous l’année' => [
            [self::position('2026-01-01', '2026-08-01'), self::position('2026-02-01', '2026-09-01')],
            QualificationGapReason::UNDER_ONE_YEAR,
        ];

        yield 'poste en cours commencé il y a six mois' => [
            [self::position('2026-01-13', null)],
            QualificationGapReason::UNDER_ONE_YEAR,
        ];
    }

    /**
     * @param list<array<string, mixed>> $positions
     */
    #[DataProvider('currentRoleProvider')]
    public function testCurrentRoleMonths(array $positions, QualificationGapReason $expected): void
    {
        $now = new DateTimeImmutable(self::TODAY);
        $resolver = new TitleConceptResolver(new InMemoryJobConceptCatalog([]));

        self::assertSame(
            0,
            CurrentPositionExtractor::fromPositions($positions, $resolver, $now)->months,
            'le cas de test doit bien être un manque, sinon le motif explique autre chose',
        );
        self::assertSame($expected, QualificationGapClassifier::forCurrentRoleMonths($positions, $now));
        self::assertContains($expected, QualificationMetric::CURRENT_ROLE_MONTHS->reasons());
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, QualificationGapReason}>
     */
    public static function currentRoleProvider(): iterable
    {
        yield 'aucun poste' => [[], QualificationGapReason::NO_ONGOING_POSITION];

        yield 'tous les postes sont clôturés' => [
            [self::position('2015-01-01', '2019-01-01'), self::position('2019-01-01', '2024-01-01')],
            QualificationGapReason::NO_ONGOING_POSITION,
        ];

        yield 'poste ouvert sans date de début' => [
            [self::position(null, null)],
            QualificationGapReason::ONGOING_WITHOUT_START_DATE,
        ];

        yield 'poste ouvert commencé dans le futur' => [
            [self::position('2030-01-01', null)],
            QualificationGapReason::ONGOING_WITHOUT_START_DATE,
        ];

        yield 'poste ouvert depuis dix jours' => [
            [self::position('2026-07-03', null)],
            QualificationGapReason::UNDER_ONE_MONTH,
        ];
    }

    /**
     * @param list<array<string, mixed>> $educations
     */
    #[DataProvider('educationProvider')]
    public function testEducationLevel(array $educations, QualificationGapReason $expected): void
    {
        self::assertNull(
            EducationLevelCalculator::fromEducations($educations),
            'le cas de test doit bien être un manque, sinon le motif explique autre chose',
        );
        self::assertSame($expected, QualificationGapClassifier::forEducationLevel($educations));
        self::assertContains($expected, QualificationMetric::EDUCATION_LEVEL->reasons());
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, QualificationGapReason}>
     */
    public static function educationProvider(): iterable
    {
        yield 'aucune formation' => [[], QualificationGapReason::NO_EDUCATION];

        yield 'des formations, aucun intitulé que le référentiel sache lire' => [
            [['title' => 'Parcours interne Acme'], ['title' => '']],
            QualificationGapReason::UNRECOGNIZED_TITLE,
        ];
    }

    public function testJarviUpdatedAtHasASingleReason(): void
    {
        self::assertSame(
            QualificationGapReason::SOURCE_WITHOUT_DATE,
            QualificationGapClassifier::classify(QualificationMetric::JARVI_UPDATED_AT, [], []),
        );
    }

    /**
     * Un profil compté comme en manque reçoit un motif, et il appartient à sa métrique : un motif
     * emprunté à une autre rendrait la répartition incomparable au compteur.
     */
    public function testEveryMetricAnswersWithinItsOwnReasons(): void
    {
        $positions = [self::position(null, null)];
        $educations = [['title' => 'Formation maison']];

        foreach (QualificationMetric::cases() as $metric) {
            self::assertContains(
                QualificationGapClassifier::classify($metric, $positions, $educations),
                $metric->reasons(),
                $metric->value,
            );
        }
    }

    /** @return array<string, mixed> */
    private static function position(?string $startDate, ?string $endDate): array
    {
        return [
            'title' => 'Assistante comptable',
            'companyName' => 'Acme',
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }
}
