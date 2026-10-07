<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyClusterAction;
use App\Enum\VocabularyType;

/**
 * Étape 2 du pipeline vocabulaire — le chaînon entre `harvest` (récolte déterministe) et `import`
 * (chargement en base). Prend les termes récurrents PAS ENCORE rattachés à un concept, les fait
 * regrouper par le {@see VocabularyClusteringClient}, et fusionne le résultat dans la taxonomie
 * (`taxonomy.json`). N'appelle jamais la base : sa sortie est le JSON, relisible en `git diff`.
 *
 * Incrémentale : elle lit la taxonomie existante pour ne soumettre que la traîne fraîche et laisser
 * un terme rejoindre un concept déjà présent plutôt que d'en créer un doublon. Tout ce qu'elle
 * écrit est en statut `candidat` — la validation reste humaine.
 */
final readonly class VocabularyEnricher
{
    /** COMPETENCE → sac `competences`, METIER → sac `postes` (cf. dev-book/resources/taxonomie/README.md). */
    private const BAG = [
        VocabularyType::COMPETENCE->value => 'competences',
        VocabularyType::METIER->value => 'postes',
    ];

    /** Libellé `type` porté par chaque concept dans le JSON (le sac `postes` contient des « poste »). */
    private const CONCEPT_TYPE = [
        VocabularyType::COMPETENCE->value => 'competence',
        VocabularyType::METIER->value => 'poste',
    ];

    public function __construct(
        private VocabularyClusteringClient $client,
        private TermNormalizer $normalizer,
        private int $minFrequency = 3,
        private int $batchSize = 80,
    ) {
    }

    /**
     * @param list<HarvestedTerm>           $harvested termes récoltés (la récolte reste au périmètre appelant)
     * @param array<string, mixed>          $taxonomy  taxonomie existante (déjà décodée)
     * @param (callable(string): void)|null $log       trace de progression optionnelle
     *
     * @return array<string, mixed> taxonomie enrichie
     */
    public function enrich(array $harvested, array $taxonomy, VocabularyEnrichReport $report, ?callable $log = null): array
    {
        $byType = [];
        foreach ($harvested as $term) {
            $byType[$term->type->value][] = $term;
        }

        foreach (VocabularyType::cases() as $type) {
            $taxonomy = $this->enrichBag($type, $byType[$type->value] ?? [], $taxonomy, $report, $log);
        }

        return $taxonomy;
    }

    /**
     * @param list<HarvestedTerm>           $harvested
     * @param array<string, mixed>          $taxonomy
     * @param (callable(string): void)|null $log
     *
     * @return array<string, mixed>
     */
    private function enrichBag(VocabularyType $type, array $harvested, array $taxonomy, VocabularyEnrichReport $report, ?callable $log): array
    {
        $bag = self::BAG[$type->value];
        $taxonomy[$bag]['concepts'] ??= [];

        $covered = $this->coveredTerms($taxonomy[$bag]['concepts']);
        $pending = $this->pendingTerms($harvested, $covered);
        if ([] === $pending) {
            if (null !== $log) {
                $log(\sprintf('%s : rien à enrichir (traîne déjà couverte ou sous le seuil).', $bag));
            }

            return $taxonomy;
        }

        $frequencies = [];
        foreach ($pending as $term) {
            $frequencies[$term->term] = $term->frequency;
        }
        $existingConcepts = $this->conceptLabels($taxonomy[$bag]['concepts']);

        foreach (array_chunk($pending, max(1, $this->batchSize)) as $index => $chunk) {
            if (null !== $log) {
                $log(\sprintf('%s : lot %d (%d termes)…', $bag, $index + 1, \count($chunk)));
            }
            $report->termsSubmitted += \count($chunk);

            foreach ($this->client->cluster($type, $chunk, $existingConcepts) as $decision) {
                $taxonomy[$bag]['concepts'] = $this->applyDecision(
                    $type,
                    $taxonomy[$bag]['concepts'],
                    $decision,
                    $frequencies[$decision->term] ?? 0,
                    $existingConcepts,
                    $report,
                );
            }
        }

        return $taxonomy;
    }

    /**
     * @param list<array<string, mixed>> $concepts
     *
     * @return array<string, true> clés = libellés vus (canoniques + synonymes), en minuscules
     */
    private function coveredTerms(array $concepts): array
    {
        $covered = [];
        foreach ($concepts as $concept) {
            $covered[$this->key((string) ($concept['label_canonique'] ?? ''))] = true;
            foreach (($concept['synonymes'] ?? []) as $syn) {
                if (\is_array($syn)) {
                    $covered[$this->key((string) ($syn['terme'] ?? ''))] = true;
                }
            }
        }
        unset($covered['']);

        return $covered;
    }

    /**
     * @param list<HarvestedTerm> $harvested
     * @param array<string, true> $covered
     *
     * @return list<TermToCluster> termes récurrents non couverts, du plus fréquent au moins fréquent
     */
    private function pendingTerms(array $harvested, array $covered): array
    {
        $pending = [];
        foreach ($harvested as $term) {
            if ($term->frequency >= $this->minFrequency && !isset($covered[$this->key($term->term)])) {
                $pending[] = new TermToCluster($term->term, $term->frequency);
            }
        }
        usort($pending, static fn (TermToCluster $a, TermToCluster $b): int => $b->frequency <=> $a->frequency);

        return $pending;
    }

    /**
     * @param list<array<string, mixed>> $concepts
     *
     * @return list<string>
     */
    private function conceptLabels(array $concepts): array
    {
        $labels = [];
        foreach ($concepts as $concept) {
            $label = (string) ($concept['label_canonique'] ?? '');
            if ('' !== $label) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * @param list<array<string, mixed>> $concepts
     * @param list<string>               $existingConcepts référence : enrichie quand un concept naît
     *
     * @return list<array<string, mixed>>
     */
    private function applyDecision(VocabularyType $type, array $concepts, ClusterDecision $decision, int $frequency, array &$existingConcepts, VocabularyEnrichReport $report): array
    {
        if (VocabularyClusterAction::NOISE === $decision->action || '' === trim($decision->conceptLabel)) {
            ++$report->noiseDropped;

            return $concepts;
        }

        $target = $this->indexOfConcept($concepts, $decision->conceptLabel);
        if (null === $target) {
            $concepts[] = $this->newConcept($type, $decision->conceptLabel);
            $target = array_key_last($concepts);
            $existingConcepts[] = $decision->conceptLabel;
            ++$report->conceptsCreated;
        }

        if ($this->attachSynonym($concepts[$target], $decision, $frequency)) {
            ++$report->synonymsAttached;
        }

        return $concepts;
    }

    /**
     * @param list<array<string, mixed>> $concepts
     */
    private function indexOfConcept(array $concepts, string $label): ?int
    {
        $needle = $this->key($label);
        foreach ($concepts as $index => $concept) {
            if ($this->key((string) ($concept['label_canonique'] ?? '')) === $needle) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function newConcept(VocabularyType $type, string $label): array
    {
        return [
            'label_canonique' => $label,
            'type' => self::CONCEPT_TYPE[$type->value],
            'frequence_totale' => 0,
            'statut' => 'candidat',
            'synonymes' => [],
        ];
    }

    /**
     * Ajoute le terme comme synonyme du concept s'il n'y figure pas déjà (même terme, même langue),
     * et cumule sa fréquence dans le total du concept. Retourne false si c'était un doublon.
     *
     * @param array<string, mixed> $concept
     */
    private function attachSynonym(array &$concept, ClusterDecision $decision, int $frequency): bool
    {
        $lang = '' !== trim($decision->lang) ? $decision->lang : 'fr';
        foreach (($concept['synonymes'] ?? []) as $syn) {
            if (\is_array($syn)
                && $this->key((string) ($syn['terme'] ?? '')) === $this->key($decision->term)
                && (string) ($syn['langue'] ?? '') === $lang
            ) {
                return false;
            }
        }

        $concept['synonymes'][] = [
            'terme' => $decision->term,
            'frequence' => $frequency,
            'langue' => $lang,
        ];
        $concept['frequence_totale'] = (int) ($concept['frequence_totale'] ?? 0) + $frequency;

        return true;
    }

    /**
     * MÊME normalisation que la récolte : c'est elle qui décide si un terme est déjà couvert. Une
     * forme divergente ici ferait resoumettre au LLM des termes déjà rattachés, et créerait des
     * synonymes en double.
     */
    private function key(string $value): string
    {
        return $this->normalizer->normalize($value);
    }
}
