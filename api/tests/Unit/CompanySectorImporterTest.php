<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\CompanySectorImporter;
use App\Entity\CompanySector;
use App\Enum\Sector;
use App\Enum\VocabularyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Chargement de sectors.json en base (task 107), EntityManager mocké : upsert idempotent sur la clé
 * stable, rafraîchissement d'une entrée connue, rejet de ce qui n'est pas exploitable, dry-run sans
 * écriture.
 */
final class CompanySectorImporterTest extends TestCase
{
    public function testCreatesUnknownCompaniesAndUpdatesKnownOnes(): void
    {
        $known = new CompanySector('lci-1', 'Ancien nom', Sector::AUTRE, 1);

        $repository = self::createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?CompanySector => 'lci-1' === $criteria['companyKey'] ? $known : null,
        );

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('persist')->willReturnCallback(static function (object $e) use (&$persisted): void {
            $persisted[] = $e;
        });
        $em->expects(self::once())->method('flush');

        $report = (new CompanySectorImporter($em))->import(['companies' => [
            'lci-1' => ['name' => 'Deloitte', 'sector' => 'CONSEIL_AUDIT', 'frequence' => 20],
            'lci-2' => ['name' => 'Safran', 'sector' => 'AERONAUTIQUE', 'frequence' => 46],
            'name:freelance' => ['name' => 'Freelance', 'sector' => 'INDEPENDANT', 'frequence' => 60],
        ]]);

        self::assertSame(2, $report->created);
        self::assertSame(1, $report->updated);
        self::assertSame(0, $report->skipped);

        // l'entrée connue est rafraîchie sur place, pas dupliquée
        self::assertSame('Deloitte', $known->name);
        self::assertSame(Sector::CONSEIL_AUDIT, $known->sector);
        self::assertSame(20, $known->frequency);

        self::assertCount(2, $persisted);
        self::assertSame(Sector::AERONAUTIQUE, $persisted[0]->sector);
        self::assertSame(VocabularyStatus::CANDIDAT, $persisted[0]->status);
    }

    public function testSkipsUnusableEntries(): void
    {
        $repository = self::createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects(self::never())->method('persist');

        $report = (new CompanySectorImporter($em))->import(['companies' => [
            'lci-1' => ['name' => 'X', 'sector' => 'SECTEUR_INEXISTANT', 'frequence' => 3],
            '' => ['name' => 'Sans clé', 'sector' => 'INDUSTRIE', 'frequence' => 3],
            'lci-2' => 'pas un objet',
        ]]);

        self::assertSame(0, $report->created);
        self::assertSame(3, $report->skipped);
    }

    public function testDryRunDoesNotFlush(): void
    {
        $repository = self::createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects(self::never())->method('flush');

        $report = (new CompanySectorImporter($em))->import(
            ['companies' => ['lci-1' => ['name' => 'Safran', 'sector' => 'AERONAUTIQUE', 'frequence' => 46]]],
            dryRun: true,
        );

        self::assertSame(1, $report->created);
    }
}
