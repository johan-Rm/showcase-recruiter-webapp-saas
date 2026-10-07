<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\ConceptSimilarity;
use App\Domain\Vocabulary\ConceptSimilarityCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Proximité entre concepts par co-occurrence (task 111). Les concepts sont désignés par leur
 * identifiant : 1 et 2 forment une paire toujours ensemble, 9 est le concept « fourre-tout »
 * présent partout — c'est lui que le NPMI doit éteindre.
 */
final class ConceptSimilarityCalculatorTest extends TestCase
{
    public function testPairsSeenTogetherAreClose(): void
    {
        $profiles = array_merge(array_fill(0, 10, [1, 2]), array_fill(0, 10, [3, 4]));

        $similarities = (new ConceptSimilarityCalculator(minCooccurrences: 4, minNpmi: 0.3))->calculate($profiles);

        self::assertCount(2, $similarities);
        self::assertSame([1, 2], [$similarities[0]->conceptAId, $similarities[0]->conceptBId]);
        self::assertSame(10, $similarities[0]->cooccurrences);
        self::assertSame(1.0, $similarities[0]->jaccard, 'jamais vus l’un sans l’autre');
        self::assertSame(1.0, $similarities[0]->npmi, 'association parfaite');
    }

    /**
     * Cas dégénéré : une paire portée par TOUS les profils. L'association est parfaite mais
     * n'apprend rien, et la formule du NPMI y diviserait par zéro.
     */
    public function testPairCoveringTheWholeVivierIsNotClose(): void
    {
        self::assertSame([], (new ConceptSimilarityCalculator(minCooccurrences: 4, minNpmi: 0.3))
            ->calculate(array_fill(0, 10, [1, 2])));
    }

    /**
     * Critère d'acceptation de la carte : « un concept trop fréquent ne ressort pas proche de tout ».
     * Le concept 9 est sur les 20 profils ; il co-occurre donc massivement avec 1 et 2 sans rien
     * apprendre. Seule la vraie paire doit survivre.
     */
    public function testUbiquitousConceptIsNotCloseToEverything(): void
    {
        $profiles = [];
        for ($i = 0; $i < 10; ++$i) {
            $profiles[] = [1, 2, 9];
        }
        for ($i = 0; $i < 10; ++$i) {
            $profiles[] = [3, 9];
        }

        $pairs = (new ConceptSimilarityCalculator(minCooccurrences: 4, minNpmi: 0.3))->calculate($profiles);

        self::assertSame([[1, 2]], array_map(
            static fn (ConceptSimilarity $s): array => [$s->conceptAId, $s->conceptBId],
            $pairs,
        ));
    }

    public function testDropsPairsBelowTheCooccurrenceFloor(): void
    {
        $profiles = [[1, 2], [1, 2], [1, 2], [3], [3], [3], [3]];

        self::assertSame([], (new ConceptSimilarityCalculator(minCooccurrences: 4, minNpmi: 0.0))->calculate($profiles));
        self::assertCount(1, (new ConceptSimilarityCalculator(minCooccurrences: 3, minNpmi: 0.0))->calculate($profiles));
    }

    public function testResultsAreOrderedByDecreasingNpmi(): void
    {
        $profiles = [];
        for ($i = 0; $i < 8; ++$i) {
            $profiles[] = [1, 2];       // paire exclusive
        }
        for ($i = 0; $i < 8; ++$i) {
            $profiles[] = [1, 3, 4];    // 1 traîne aussi ailleurs
        }

        $pairs = (new ConceptSimilarityCalculator(minCooccurrences: 4, minNpmi: 0.0))->calculate($profiles);

        $scores = array_map(static fn (ConceptSimilarity $s): float => $s->npmi, $pairs);
        $sorted = $scores;
        rsort($sorted);
        self::assertSame($sorted, $scores);
    }

    public function testHandlesAnEmptyVivier(): void
    {
        self::assertSame([], (new ConceptSimilarityCalculator())->calculate([]));
    }
}
