<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\ExperienceYearsCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Jarvi ne donne pas les années d'expérience : on les dérive des périodes des postes. Le piège est
 * le cumul d'emploi — deux postes menés la même année font UNE année vécue, pas deux.
 */
final class ExperienceYearsCalculatorTest extends TestCase
{
    private const string TODAY = '2026-07-13';

    /**
     * @param list<array<string, mixed>> $positions
     */
    #[DataProvider('positionsProvider')]
    public function testFromPositions(array $positions, int $expected): void
    {
        self::assertSame(
            $expected,
            ExperienceYearsCalculator::fromPositions($positions, new DateTimeImmutable(self::TODAY)),
        );
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, int}>
     */
    public static function positionsProvider(): iterable
    {
        yield 'postes consécutifs' => [[
            self::position('2015-01-01', '2019-01-01'),
            self::position('2019-01-01', '2026-01-01'),
        ], 11];

        yield 'poste en cours (sans date de fin) : compté jusqu’à aujourd’hui' => [[
            self::position('2021-06-01', null),
        ], 5];

        yield 'cumul d’emploi : les périodes qui se chevauchent fusionnent' => [[
            self::position('2022-01-01', '2026-01-01'),
            self::position('2022-01-01', '2026-01-01'),
        ], 4];

        yield 'chevauchement partiel' => [[
            self::position('2020-01-01', '2024-01-01'),
            self::position('2023-01-01', '2026-01-01'),
        ], 6];

        yield 'trou entre deux postes : non compté' => [[
            self::position('2010-01-01', '2012-01-01'),
            self::position('2014-01-01', '2016-01-01'),
        ], 4];

        yield 'format timestamptz Jarvi' => [[
            self::position('2015-03-01T00:00:00+00:00', '2026-03-01T00:00:00+00:00'),
        ], 11];

        yield 'sans date de début : période non mesurable' => [[self::position(null, '2020-01-01')], 0];
        yield 'fin antérieure au début : donnée incohérente' => [[self::position('2020-01-01', '2015-01-01')], 0];
        yield 'date future' => [[self::position('2030-01-01', null)], 0];
        yield 'aucun poste' => [[], 0];
    }

    /**
     * @return array<string, mixed>
     */
    private static function position(?string $startDate, ?string $endDate): array
    {
        return [
            'title' => 'Développeur Symfony senior',
            'companyName' => 'Société Exemple',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'source' => 'RESUME',
        ];
    }
}
