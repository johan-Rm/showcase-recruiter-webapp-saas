<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\JobConceptEntry;
use App\Domain\Vocabulary\ResolvedJob;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Tests\Support\InMemoryJobConceptCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Reconnaissance d'un concept métier dans un intitulé (task 118). Les cas viennent des intitulés
 * réels du vivier : ce sont eux qui ont motivé les trois règles d'arbitrage.
 */
final class TitleConceptResolverTest extends TestCase
{
    private const int STAGE = 1;
    private const int AUDITEUR = 2;
    private const int MANAGER = 3;
    private const int CHEF_PROJET = 4;
    private const int EXPLOITATION = 5;

    public function testExactLabelWins(): void
    {
        $job = $this->resolver()->resolve('auditeur');

        self::assertInstanceOf(ResolvedJob::class, $job);
        self::assertSame(self::AUDITEUR, $job->conceptId);
        self::assertTrue($job->exact);
    }

    /** Sans cette règle, « Stage », concept de poste le plus fréquent, raflerait tout. */
    public function testJobBeatsStatus(): void
    {
        $job = $this->resolver()->resolve('stage auditeur interne');

        self::assertInstanceOf(ResolvedJob::class, $job);
        self::assertSame(self::AUDITEUR, $job->conceptId);
        self::assertFalse($job->exact);
        self::assertFalse($job->isStatus);
    }

    /** Faute de métier reconnu, le statut reste la meilleure réponse disponible. */
    public function testStatusSurvivesWhenNoJobMatches(): void
    {
        $job = $this->resolver()->resolve('hospital internship');

        self::assertInstanceOf(ResolvedJob::class, $job);
        self::assertSame(self::STAGE, $job->conceptId);
        self::assertTrue($job->isStatus);
    }

    /** Le libellé le plus long en mots l'emporte sur le terme générique qu'il contient. */
    public function testMostSpecificLabelWins(): void
    {
        $job = $this->resolver()->resolve('site operations manager');

        self::assertInstanceOf(ResolvedJob::class, $job);
        self::assertSame(self::EXPLOITATION, $job->conceptId, '« operations manager » bat « manager »');
    }

    /** Un mot entier, jamais une sous-chaîne : « manager » ne doit pas se déclencher sur « management ». */
    public function testDoesNotMatchInsideAnotherWord(): void
    {
        self::assertNull($this->resolver()->resolve('management assistant'));
    }

    public function testUnknownTitleResolvesToNothing(): void
    {
        self::assertNull($this->resolver()->resolve('métier totalement inconnu'));
        self::assertNull($this->resolver()->resolve(''));
    }

    private function resolver(): TitleConceptResolver
    {
        $entries = [
            new JobConceptEntry('stage', self::STAGE, 400, true),
            new JobConceptEntry('internship', self::STAGE, 40, true),
            new JobConceptEntry('auditeur', self::AUDITEUR, 30, false),
            new JobConceptEntry('manager', self::MANAGER, 300, false),
            new JobConceptEntry('chef de projet', self::CHEF_PROJET, 100, false),
            new JobConceptEntry('operations manager', self::EXPLOITATION, 5, false),
        ];

        return new TitleConceptResolver(new InMemoryJobConceptCatalog($entries));
    }
}
