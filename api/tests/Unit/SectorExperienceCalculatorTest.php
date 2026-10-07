<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\SectorExperienceCalculator;
use App\Enum\Sector;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Expérience par secteur (task 095) : le temps se cumule par secteur, les chevauchements se
 * fusionnent DANS un secteur mais pas ENTRE secteurs, et une entreprise non classée n'invente
 * aucun secteur.
 */
final class SectorExperienceCalculatorTest extends TestCase
{
    private const string TODAY = '2026-07-13';

    /** @var array<string, Sector> */
    private const array MAP = [
        'lci-info' => Sector::INFORMATIQUE,
        'lci-banque' => Sector::BANQUE_FINANCE,
        'name:acme' => Sector::INDUSTRIE,
    ];

    public function testCumulatesMonthsPerSector(): void
    {
        $months = SectorExperienceCalculator::fromPositions([
            self::position('lci-info', null, '2020-01-13', '2022-01-13'),   // 24 mois
            self::position('lci-banque', null, '2022-01-13', '2023-01-13'), // 12 mois
        ], self::MAP, new DateTimeImmutable(self::TODAY));

        self::assertSame(['INFORMATIQUE' => 24, 'BANQUE_FINANCE' => 12], $months);
    }

    public function testMergesOverlapsInsideASectorButNotAcrossSectors(): void
    {
        $months = SectorExperienceCalculator::fromPositions([
            // deux postes informatiques sur la MÊME période → une seule fois 24 mois
            self::position('lci-info', null, '2020-01-13', '2022-01-13'),
            self::position('lci-info', null, '2020-07-13', '2021-07-13'),
            // un poste bancaire sur la même période → compté à part (cumul d'emploi)
            self::position('lci-banque', null, '2020-01-13', '2022-01-13'),
        ], self::MAP, new DateTimeImmutable(self::TODAY));

        self::assertSame(24, $months['INFORMATIQUE']);
        self::assertSame(24, $months['BANQUE_FINANCE']);
    }

    public function testFallsBackToCompanyNameWhenNoLinkedinId(): void
    {
        $months = SectorExperienceCalculator::fromPositions([
            self::position(null, 'ACME', '2024-07-13', null),   // en cours → jusqu'à aujourd'hui
        ], self::MAP, new DateTimeImmutable(self::TODAY));

        self::assertSame(['INDUSTRIE' => 24], $months);
    }

    public function testIgnoresUnclassifiedCompaniesAndUnusableDates(): void
    {
        $months = SectorExperienceCalculator::fromPositions([
            self::position('lci-inconnu', 'Inconnue', '2020-01-13', '2022-01-13'), // non classée
            self::position('lci-info', null, null, '2022-01-13'),                  // sans début
            self::position('lci-info', null, '2023-01-13', '2020-01-13'),          // fin avant début
        ], self::MAP, new DateTimeImmutable(self::TODAY));

        self::assertSame([], $months);
    }

    /**
     * @return array<string, mixed>
     */
    private static function position(?string $linkedinCompanyId, ?string $companyName, ?string $start, ?string $end): array
    {
        return [
            'linkedinCompanyId' => $linkedinCompanyId,
            'companyName' => $companyName,
            'startDate' => $start,
            'endDate' => $end,
            'title' => 'Poste',
        ];
    }
}
