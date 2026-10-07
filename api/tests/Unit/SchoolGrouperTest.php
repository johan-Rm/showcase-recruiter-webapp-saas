<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\HarvestedSchool;
use App\Domain\Vocabulary\SchoolGrouper;
use App\Domain\Vocabulary\SchoolGroupingClient;
use App\Domain\Vocabulary\SchoolGroupingDecision;
use App\Domain\Vocabulary\SchoolGroupingReport;
use PHPUnit\Framework\TestCase;

/**
 * Logique de regroupement des écoles (task 115), port LLM doublé : aucun appel réseau. On vérifie
 * ce que la commande, désactivée, ne montre pas — que deux graphies d'un même établissement se
 * ramènent à UN nom canonique, et que le regroupement est incrémental.
 */
final class SchoolGrouperTest extends TestCase
{
    public function testTwoSpellingsCollapseToOneCanonicalName(): void
    {
        $schools = [
            new HarvestedSchool('encg casablanca', 'ENCG Casablanca', 12, ['id-1'], ['ENCG Casablanca']),
            new HarvestedSchool(
                'école nationale de commerce et de gestion de casablanca',
                'École Nationale de Commerce et de Gestion de Casablanca',
                4,
                ['id-2'],
                ['École Nationale de Commerce et de Gestion de Casablanca'],
            ),
            new HarvestedSchool('emsi', 'EMSI', 9, ['id-3'], ['EMSI']),
        ];

        // Double : les deux ENCG partagent un canonique, l'EMSI garde le sien.
        $client = new class implements SchoolGroupingClient {
            public function group(array $schools): array
            {
                return array_map(static fn (HarvestedSchool $s): SchoolGroupingDecision => new SchoolGroupingDecision(
                    $s->key,
                    str_contains($s->key, 'commerce') || 'encg casablanca' === $s->key
                        ? 'École Nationale de Commerce et de Gestion de Casablanca'
                        : $s->name,
                ), $schools);
            }
        };

        $report = new SchoolGroupingReport();
        $result = (new SchoolGrouper($client))->group($schools, [], $report);

        self::assertSame(3, $report->schoolsSubmitted);
        self::assertSame(3, $report->schoolsGrouped);
        self::assertSame(2, $report->canonicalNames, 'les deux ENCG fusionnent, l’EMSI reste distincte');
        self::assertSame(
            'École Nationale de Commerce et de Gestion de Casablanca',
            $result['schools']['encg casablanca']['nom_canonique'],
        );
        self::assertSame('candidat', $result['schools']['emsi']['statut']);
    }

    public function testAlreadyGroupedSchoolsAreNotResubmitted(): void
    {
        $baseline = ['schools' => [
            'emsi' => ['nom' => 'EMSI', 'nom_canonique' => 'École Marocaine des Sciences de l’Ingénieur', 'frequence' => 9, 'identifiants_jarvi' => [], 'statut' => 'candidat'],
        ]];

        $seen = [];
        $client = new class($seen) implements SchoolGroupingClient {
            /** @param list<string> $seen */
            public function __construct(public array &$seen)
            {
            }

            public function group(array $schools): array
            {
                foreach ($schools as $school) {
                    $this->seen[] = $school->key;
                }

                return [];
            }
        };

        (new SchoolGrouper($client))->group(
            [new HarvestedSchool('emsi', 'EMSI', 9, [], ['EMSI'])],
            $baseline,
            new SchoolGroupingReport(),
        );

        self::assertNotContains('emsi', $seen, 'un établissement déjà regroupé ne doit pas repartir au LLM');
    }
}
