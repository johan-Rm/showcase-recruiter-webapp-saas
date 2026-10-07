<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\CompanySectorClassifier;
use App\Domain\Vocabulary\CompanySectorClassifyReport;
use App\Domain\Vocabulary\CompanySectorClient;
use App\Domain\Vocabulary\CompanySectorDecision;
use App\Domain\Vocabulary\CompanyToClassify;
use App\Enum\Sector;
use PHPUnit\Framework\TestCase;

/**
 * Logique de classification (task 105), port doublé : aucun appel LLM. Couvre l'écart des
 * entreprises déjà classées, le seuil de fréquence, et l'écriture de sectors.json avec le secteur
 * retenu.
 */
final class CompanySectorClassifierTest extends TestCase
{
    public function testClassifiesPendingCompaniesAndSkipsKnownOnes(): void
    {
        $existing = ['companies' => [
            'lci-1' => ['name' => 'Deloitte', 'sector' => 'CONSEIL_AUDIT', 'frequence' => 65, 'statut' => 'candidat'],
        ]];

        $companies = [
            new CompanyToClassify('lci-1', 'Deloitte', 65),          // déjà classée → ignorée
            new CompanyToClassify('lci-2', 'BNP Paribas', 28),
            new CompanyToClassify('name:freelance', 'Freelance', 58),
            new CompanyToClassify('name:xyz', 'XYZ', 1),             // sous le seuil (minFrequency=2)
        ];

        $client = new class implements CompanySectorClient {
            public function classify(array $companies): array
            {
                return array_map(static fn (CompanyToClassify $c): CompanySectorDecision => match ($c->key) {
                    'lci-2' => new CompanySectorDecision($c->key, Sector::BANQUE_FINANCE),
                    'name:freelance' => new CompanySectorDecision($c->key, Sector::INDEPENDANT),
                    default => new CompanySectorDecision($c->key, Sector::AUTRE),
                }, $companies);
            }
        };

        $report = new CompanySectorClassifyReport();
        $result = (new CompanySectorClassifier($client, minFrequency: 2, batchSize: 100))
            ->classify($companies, $existing, $report);

        self::assertSame(2, $report->companiesSubmitted);   // lci-2 + freelance (Deloitte connu, XYZ sous seuil)
        self::assertSame(2, $report->companiesClassified);

        $companiesOut = $result['companies'];
        self::assertSame('CONSEIL_AUDIT', $companiesOut['lci-1']['sector']);   // inchangé
        self::assertSame('BANQUE_FINANCE', $companiesOut['lci-2']['sector']);
        self::assertSame('INDEPENDANT', $companiesOut['name:freelance']['sector']);
        self::assertSame('candidat', $companiesOut['lci-2']['statut']);
        self::assertArrayNotHasKey('name:xyz', $companiesOut);
    }
}
