<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\JobExperienceCalculator;
use App\Domain\Vocabulary\JobConceptEntry;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Tests\Support\InMemoryJobConceptCatalog;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Expérience par métier (task 095) sur un vocabulaire simulé. Les concepts « statut » sont présents
 * à dessein : c'est le calculateur qui doit les écarter, un stagiaire n'exerçant pas le métier
 * « stagiaire ».
 */
final class JobExperienceCalculatorTest extends TestCase
{
    private const int DEVELOPPEUR = 7;
    private const int CHEF_PROJET = 9;
    private const int STAGE = 11;

    public function testCumulatesMonthsPerJob(): void
    {
        $experiences = JobExperienceCalculator::fromPositions(
            [
                ['title' => 'Développeur', 'startDate' => '2020-01-01', 'endDate' => '2022-01-01'],
                ['title' => 'Chef de projet', 'startDate' => '2022-01-01', 'endDate' => '2023-01-01'],
            ],
            $this->resolver(),
            new DateTimeImmutable('2026-01-01'),
        );

        self::assertSame(['months' => 24, 'positions' => 1, 'exactPositions' => 1], $experiences[self::DEVELOPPEUR]);
        self::assertSame(['months' => 12, 'positions' => 1, 'exactPositions' => 1], $experiences[self::CHEF_PROJET]);
    }

    /** Deux postes du même métier menés en parallèle font une période, pas deux. */
    public function testMergesOverlappingPeriodsOfTheSameJob(): void
    {
        $experiences = JobExperienceCalculator::fromPositions(
            [
                ['title' => 'Développeur', 'startDate' => '2020-01-01', 'endDate' => '2022-01-01'],
                ['title' => 'Développeur', 'startDate' => '2021-01-01', 'endDate' => '2023-01-01'],
            ],
            $this->resolver(),
            new DateTimeImmutable('2026-01-01'),
        );

        self::assertSame(36, $experiences[self::DEVELOPPEUR]['months']);
        self::assertSame(2, $experiences[self::DEVELOPPEUR]['positions'], 'les deux postes ont alimenté la ligne');
    }

    /** Un poste en cours court jusqu'à aujourd'hui, jamais au-delà. */
    public function testOngoingPositionRunsUntilToday(): void
    {
        $experiences = JobExperienceCalculator::fromPositions(
            [['title' => 'Développeur', 'startDate' => '2024-01-01', 'endDate' => null]],
            $this->resolver(),
            new DateTimeImmutable('2026-01-01'),
        );

        self::assertSame(24, $experiences[self::DEVELOPPEUR]['months']);
    }

    /** L'intitulé est canonicalisé comme à la récolte : le bruit d'entreprise ne doit pas bloquer. */
    public function testMatchesOnTheCanonicalTitle(): void
    {
        $experiences = JobExperienceCalculator::fromPositions(
            [['title' => 'Développeur | Renewables', 'startDate' => '2024-01-01', 'endDate' => '2025-01-01']],
            $this->resolver(),
            new DateTimeImmutable('2026-01-01'),
        );

        self::assertSame(12, $experiences[self::DEVELOPPEUR]['months']);
    }

    /** Un statut n'est pas un métier : « stage développeur » compte comme du développement. */
    public function testStatusTitlesDoNotBecomeJobs(): void
    {
        $experiences = JobExperienceCalculator::fromPositions(
            [
                ['title' => 'Stage', 'startDate' => '2019-01-01', 'endDate' => '2019-07-01'],
                ['title' => 'Stage développeur', 'startDate' => '2020-01-01', 'endDate' => '2021-01-01'],
            ],
            $this->resolver(),
            new DateTimeImmutable('2026-01-01'),
        );

        self::assertArrayNotHasKey(self::STAGE, $experiences, 'le statut seul ne produit aucun métier');
        self::assertSame(12, $experiences[self::DEVELOPPEUR]['months']);
        self::assertSame(0, $experiences[self::DEVELOPPEUR]['exactPositions'], 'reconnu partiellement');
    }

    public function testIgnoresUnknownTitlesAndUnusableDates(): void
    {
        $experiences = JobExperienceCalculator::fromPositions(
            [
                ['title' => 'Métier inconnu du dictionnaire', 'startDate' => '2020-01-01', 'endDate' => '2022-01-01'],
                ['title' => 'Développeur', 'startDate' => null, 'endDate' => '2022-01-01'],
                ['title' => 'Chef de projet', 'startDate' => '2023-01-01', 'endDate' => '2020-01-01'],
            ],
            $this->resolver(),
            new DateTimeImmutable('2026-01-01'),
        );

        self::assertSame([], $experiences, 'aucun métier inventé faute de données exploitables');
    }

    private function resolver(): TitleConceptResolver
    {
        return new TitleConceptResolver(new InMemoryJobConceptCatalog([
            new JobConceptEntry('développeur', self::DEVELOPPEUR, 50, false),
            new JobConceptEntry('chef de projet', self::CHEF_PROJET, 40, false),
            new JobConceptEntry('stage', self::STAGE, 400, true),
        ]));
    }
}
