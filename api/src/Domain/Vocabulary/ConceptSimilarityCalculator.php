<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Calcule les concepts PROCHES par co-occurrence dans les profils (task 111) — déterministe, sans
 * LLM : la proximité vient des données du vivier, pas d'un jugement.
 *
 * Deux mesures complémentaires :
 * - **Jaccard** = part de profils communs. Lisible, mais favorise les concepts rares.
 * - **NPMI** = surprise de l'association, ramenée dans [-1, 1]. C'est lui qui **éteint les concepts
 *   trop fréquents** : « Communication » co-occurre avec presque tout, donc n'est proche de rien en
 *   particulier. Mesuré sur le vivier, il passe de 344 voisins à 5 une fois le seuil appliqué.
 *
 * Deux seuils, tous deux nécessaires : un plancher de co-occurrences écarte le hasard des petits
 * nombres, le plancher de NPMI écarte les associations banales.
 */
final readonly class ConceptSimilarityCalculator
{
    public function __construct(
        private int $minCooccurrences = 4,
        private float $minNpmi = 0.3,
    ) {
    }

    /**
     * @param list<list<int>> $profiles concepts portés par chaque profil (identifiants)
     *
     * @return list<ConceptSimilarity>
     */
    public function calculate(array $profiles): array
    {
        $profileCount = \count($profiles);
        if (0 === $profileCount) {
            return [];
        }

        /** @var array<int, int> $carriers */
        $carriers = [];
        /** @var array<string, int> $together */
        $together = [];
        foreach ($profiles as $concepts) {
            $concepts = array_values(array_unique($concepts));
            sort($concepts);
            foreach ($concepts as $i => $a) {
                $carriers[$a] = ($carriers[$a] ?? 0) + 1;
                foreach (\array_slice($concepts, $i + 1) as $b) {
                    $key = $a.':'.$b;
                    $together[$key] = ($together[$key] ?? 0) + 1;
                }
            }
        }

        $similarities = [];
        foreach ($together as $key => $both) {
            if ($both < $this->minCooccurrences) {
                continue;
            }

            [$a, $b] = array_map(intval(...), explode(':', $key));
            $npmi = $this->npmi($both, $carriers[$a], $carriers[$b], $profileCount);
            if ($npmi < $this->minNpmi) {
                continue;
            }

            $union = $carriers[$a] + $carriers[$b] - $both;
            $similarities[] = new ConceptSimilarity($a, $b, $both, $both / $union, $npmi);
        }

        usort($similarities, static fn (ConceptSimilarity $x, ConceptSimilarity $y): int => $y->npmi <=> $x->npmi);

        return $similarities;
    }

    /**
     * NPMI = PMI / -log p(a,b). Vaut 1 quand les deux concepts n'apparaissent jamais l'un sans
     * l'autre, 0 quand ils sont indépendants, négatif quand ils s'évitent.
     */
    private function npmi(int $both, int $carriersA, int $carriersB, int $profileCount): float
    {
        // La paire couvre TOUT le vivier : association parfaite, mais aucun pouvoir discriminant —
        // et -log(1) = 0 rendrait la division impossible.
        if ($both >= $profileCount) {
            return 0.0;
        }

        $pAb = $both / $profileCount;
        $pA = $carriersA / $profileCount;
        $pB = $carriersB / $profileCount;

        return log($pAb / ($pA * $pB)) / -log($pAb);
    }
}
