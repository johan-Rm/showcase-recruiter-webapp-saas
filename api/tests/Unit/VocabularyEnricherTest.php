<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\ClusterDecision;
use App\Domain\Vocabulary\HarvestedTerm;
use App\Domain\Vocabulary\TermNormalizer;
use App\Domain\Vocabulary\TermToCluster;
use App\Domain\Vocabulary\VocabularyClusteringClient;
use App\Domain\Vocabulary\VocabularyEnricher;
use App\Domain\Vocabulary\VocabularyEnrichReport;
use App\Enum\VocabularyClusterAction;
use App\Enum\VocabularyType;
use PHPUnit\Framework\TestCase;

/**
 * Logique de fusion de l'enricher (tâche 096), port de clustering doublé : aucun appel LLM.
 * Couvre le seuil de fréquence, l'exclusion des termes déjà couverts, le rattachement à un concept
 * existant, la création dédoublonnée d'un concept, et l'écart du bruit.
 */
final class VocabularyEnricherTest extends TestCase
{
    public function testMergesDecisionsIntoTaxonomy(): void
    {
        $taxonomy = [
            'competences' => ['concepts' => [[
                'label_canonique' => 'Gestion de projet',
                'type' => 'competence',
                'frequence_totale' => 420,
                'statut' => 'candidat',
                'synonymes' => [['terme' => 'gestion de projet', 'frequence' => 420, 'langue' => 'fr']],
            ]]],
            'postes' => ['concepts' => []],
        ];

        $harvested = [
            new HarvestedTerm(VocabularyType::COMPETENCE, 'project management', 233),
            new HarvestedTerm(VocabularyType::COMPETENCE, 'kubernetes', 40),
            new HarvestedTerm(VocabularyType::COMPETENCE, 'k8s', 10),
            new HarvestedTerm(VocabularyType::COMPETENCE, 'gestion de projet', 420), // déjà couvert
            new HarvestedTerm(VocabularyType::COMPETENCE, '(bt', 5),                 // bruit
            new HarvestedTerm(VocabularyType::COMPETENCE, 'rare', 1),                // sous le seuil
            new HarvestedTerm(VocabularyType::METIER, 'développeur', 50),
        ];

        $client = new class implements VocabularyClusteringClient {
            public function cluster(VocabularyType $type, array $terms, array $existingConcepts): array
            {
                return array_map(static fn (TermToCluster $t): ClusterDecision => match ($t->term) {
                    'project management' => new ClusterDecision($t->term, VocabularyClusterAction::ATTACH, 'Gestion de projet', 'en'),
                    'kubernetes' => new ClusterDecision($t->term, VocabularyClusterAction::NEW, 'Kubernetes', 'en'),
                    'k8s' => new ClusterDecision($t->term, VocabularyClusterAction::NEW, 'Kubernetes', 'en'),
                    '(bt' => new ClusterDecision($t->term, VocabularyClusterAction::NOISE, '', 'fr'),
                    'développeur' => new ClusterDecision($t->term, VocabularyClusterAction::NEW, 'Développeur', 'fr'),
                    default => new ClusterDecision($t->term, VocabularyClusterAction::NOISE, '', 'fr'),
                }, $terms);
            }
        };

        $report = new VocabularyEnrichReport();
        $result = (new VocabularyEnricher($client, new TermNormalizer(), minFrequency: 3, batchSize: 80))
            ->enrich($harvested, $taxonomy, $report);

        // Termes soumis : les 5 récurrents non couverts (ni « gestion de projet » ni « rare »).
        self::assertSame(5, $report->termsSubmitted);
        self::assertSame(2, $report->conceptsCreated);     // Kubernetes + Développeur
        self::assertSame(4, $report->synonymsAttached);    // project management, kubernetes, k8s, développeur
        self::assertSame(1, $report->noiseDropped);        // « (bt »

        $competences = $result['competences']['concepts'];
        self::assertContains('project management', $this->synonyms($competences, 'Gestion de projet'));

        $kubernetes = $this->concept($competences, 'Kubernetes');
        self::assertSame(['kubernetes', 'k8s'], $this->synonyms($competences, 'Kubernetes'));
        self::assertSame(50, $kubernetes['frequence_totale']);
        self::assertSame('candidat', $kubernetes['statut']);

        self::assertNotNull($this->concept($result['postes']['concepts'], 'Développeur'));
    }

    public function testDoesNotResubmitAlreadyCoveredTerms(): void
    {
        $taxonomy = ['competences' => ['concepts' => [[
            'label_canonique' => 'Anglais',
            'synonymes' => [['terme' => 'english', 'frequence' => 100, 'langue' => 'en']],
        ]]], 'postes' => ['concepts' => []]];

        $seen = [];
        $client = new class($seen) implements VocabularyClusteringClient {
            /** @param list<string> $seen */
            public function __construct(public array &$seen)
            {
            }

            public function cluster(VocabularyType $type, array $terms, array $existingConcepts): array
            {
                foreach ($terms as $t) {
                    $this->seen[] = $t->term;
                }

                return [];
            }
        };

        (new VocabularyEnricher($client, new TermNormalizer(), minFrequency: 3))->enrich(
            [new HarvestedTerm(VocabularyType::COMPETENCE, 'english', 100)],
            $taxonomy,
            new VocabularyEnrichReport(),
        );

        self::assertNotContains('english', $seen, 'Un terme déjà présent comme synonyme ne doit pas être resoumis.');
    }

    /**
     * La couverture se juge sur la MÊME forme que la récolte. Avec une normalisation locale
     * (`mb_strtolower(trim())`), l'apostrophe typographique de la taxonomie ne reconnaissait pas
     * l'apostrophe droite produite par `harvest` : le terme repartait au LLM et se dédoublait.
     */
    public function testCoverageIgnoresTypographicApostrophes(): void
    {
        $taxonomy = ['competences' => ['concepts' => [[
            'label_canonique' => "Travail d'équipe",
            'synonymes' => [['terme' => 'travail d’équipe', 'frequence' => 308, 'langue' => 'fr']],
        ]]], 'postes' => ['concepts' => []]];

        $seen = [];
        $client = new class($seen) implements VocabularyClusteringClient {
            /** @param list<string> $seen */
            public function __construct(public array &$seen)
            {
            }

            public function cluster(VocabularyType $type, array $terms, array $existingConcepts): array
            {
                foreach ($terms as $t) {
                    $this->seen[] = $t->term;
                }

                return [];
            }
        };

        (new VocabularyEnricher($client, new TermNormalizer(), minFrequency: 3))->enrich(
            [new HarvestedTerm(VocabularyType::COMPETENCE, "travail d'équipe", 308)],
            $taxonomy,
            new VocabularyEnrichReport(),
        );

        self::assertSame([], $seen, 'Les deux graphies d’apostrophe désignent le même terme.');
    }

    /**
     * @param list<array<string, mixed>> $concepts
     *
     * @return array<string, mixed>|null
     */
    private function concept(array $concepts, string $label): ?array
    {
        foreach ($concepts as $concept) {
            if (($concept['label_canonique'] ?? null) === $label) {
                return $concept;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $concepts
     *
     * @return list<string>
     */
    private function synonyms(array $concepts, string $label): array
    {
        $concept = $this->concept($concepts, $label) ?? [];

        return array_map(static fn (array $s): string => (string) $s['terme'], $concept['synonymes'] ?? []);
    }
}
