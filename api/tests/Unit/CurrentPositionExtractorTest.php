<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\CurrentPosition;
use App\Domain\Jarvi\CurrentPositionExtractor;
use App\Domain\Vocabulary\JobConceptEntry;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Enum\CurrentPositionCertainty;
use App\Tests\Support\InMemoryJobConceptCatalog;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Poste actuel (task 117). Les cas couverts sont ceux mesurés sur le vivier : 53 % de profils non
 * ambigus, 30 % sans poste en cours, 18 % avec plusieurs — dont une part de promotions internes.
 */
final class CurrentPositionExtractorTest extends TestCase
{
    private const int DEVELOPPEUR = 7;
    private const int STAGE = 11;
    private const string NOW = '2026-01-01';

    public function testSingleOngoingPositionIsCertain(): void
    {
        $current = $this->extract([
            ['title' => 'Développeur', 'companyName' => 'ACME', 'startDate' => '2024-01-01', 'endDate' => null],
            ['title' => 'Vendeur', 'companyName' => 'Beta', 'startDate' => '2018-01-01', 'endDate' => '2023-01-01'],
        ]);

        self::assertSame(CurrentPositionCertainty::CERTAIN, $current->certainty);
        self::assertSame('Développeur', $current->title);
        self::assertSame('ACME', $current->companyName);
        self::assertSame(self::DEVELOPPEUR, $current->conceptId);
        self::assertSame(24, $current->months);
    }

    /** Une promotion laisse deux lignes ouvertes chez le même employeur : ce n'est pas une ambiguïté. */
    public function testTwoOngoingPositionsInTheSameCompanyStayCertain(): void
    {
        $current = $this->extract([
            ['title' => 'Développeur', 'companyName' => 'ACME', 'startDate' => '2020-01-01', 'endDate' => null],
            ['title' => 'Chef de projet', 'companyName' => 'acme', 'startDate' => '2023-01-01', 'endDate' => null],
        ]);

        self::assertSame(CurrentPositionCertainty::CERTAIN, $current->certainty);
        self::assertSame('Chef de projet', $current->title, 'la plus récemment commencée');
    }

    /** Deux employeurs concurrents : on expose la plus récente, mais on ne prétend pas trancher. */
    public function testTwoOngoingPositionsInDifferentCompaniesAreAmbiguous(): void
    {
        $current = $this->extract([
            ['title' => 'Développeur', 'companyName' => 'ACME', 'startDate' => '2020-01-01', 'endDate' => null],
            ['title' => 'Développeur', 'companyName' => 'Beta', 'startDate' => '2023-01-01', 'endDate' => null],
        ]);

        self::assertSame(CurrentPositionCertainty::AMBIGUOUS, $current->certainty);
        self::assertSame('Beta', $current->companyName);
    }

    /** Aucun poste en cours n'est PAS un poste vide : les deux ne se confondent pas. */
    public function testNoOngoingPositionIsAbsent(): void
    {
        $current = $this->extract([
            ['title' => 'Développeur', 'companyName' => 'ACME', 'startDate' => '2018-01-01', 'endDate' => '2023-01-01'],
        ]);

        self::assertSame(CurrentPositionCertainty::ABSENT, $current->certainty);
        self::assertNull($current->title);
        self::assertNull($current->companyName);
        self::assertSame(0, $current->months);
    }

    /** Sans date de début, le poste existe mais sa durée reste inconnue — pas « zéro mois d'ancienneté ». */
    public function testMissingStartDateYieldsNoDuration(): void
    {
        $current = $this->extract([
            ['title' => 'Développeur', 'companyName' => 'ACME', 'startDate' => null, 'endDate' => null],
        ]);

        self::assertSame(CurrentPositionCertainty::CERTAIN, $current->certainty);
        self::assertSame('Développeur', $current->title);
        self::assertSame(0, $current->months);
    }

    /** Un statut n'est pas un métier : le poste est exposé, sans concept métier. */
    public function testStatusTitleCarriesNoJobConcept(): void
    {
        $current = $this->extract([
            ['title' => 'Stage', 'companyName' => 'ACME', 'startDate' => '2025-01-01', 'endDate' => null],
        ]);

        self::assertSame('Stage', $current->title);
        self::assertNull($current->conceptId);
    }

    /** @param list<array<string, mixed>> $positions */
    private function extract(array $positions): CurrentPosition
    {
        $resolver = new TitleConceptResolver(new InMemoryJobConceptCatalog([
            new JobConceptEntry('développeur', self::DEVELOPPEUR, 50, false),
            new JobConceptEntry('chef de projet', 9, 40, false),
            new JobConceptEntry('stage', self::STAGE, 400, true),
        ]));

        return CurrentPositionExtractor::fromPositions($positions, $resolver, new DateTimeImmutable(self::NOW));
    }
}
