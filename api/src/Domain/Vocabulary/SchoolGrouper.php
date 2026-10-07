<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Regroupe les établissements du vivier sous un nom CANONIQUE, via le {@see SchoolGroupingClient}
 * (task 115). Étape hors ligne : n'écrit que `schools.json` (relu en `git diff`), jamais la base.
 * Tout entre en statut `candidat` — la validation reste humaine.
 *
 * Raison d'être : l'égalité de chaîne ne fusionne que 3,2 % des noms (mesuré) ; les doublons qui
 * restent sont sémantiques (« ENCG Casablanca » = « École Nationale de Commerce et de Gestion de
 * Casablanca »). Seul un jugement de sens les rapproche — d'où le LLM.
 *
 * Incrémentale : elle lit `schools.json` pour ne soumettre que les clés absentes.
 */
final readonly class SchoolGrouper
{
    public function __construct(
        private SchoolGroupingClient $client,
        private int $minFrequency = 1,
        private int $batchSize = 100,
    ) {
    }

    /**
     * @param list<HarvestedSchool>         $schools  récoltés (la récolte reste au périmètre appelant)
     * @param array<string, mixed>          $baseline contenu existant de schools.json (déjà décodé)
     * @param (callable(string): void)|null $log
     *
     * @return array<string, mixed> schools.json enrichi
     */
    public function group(array $schools, array $baseline, SchoolGroupingReport $report, ?callable $log = null): array
    {
        $baseline['schools'] ??= [];

        $pending = [];
        foreach ($schools as $school) {
            if ($school->frequency >= $this->minFrequency && !isset($baseline['schools'][$school->key])) {
                $pending[$school->key] = $school;
            }
        }
        if ([] === $pending) {
            if (null !== $log) {
                $log('Aucun établissement à regrouper (tous déjà traités ou sous le seuil).');
            }

            return $this->recount($baseline, $report);
        }

        foreach (array_chunk($pending, max(1, $this->batchSize), true) as $index => $chunk) {
            if (null !== $log) {
                $log(\sprintf('Lot %d (%d établissements)…', $index + 1, \count($chunk)));
            }
            $report->schoolsSubmitted += \count($chunk);

            foreach ($this->client->group(array_values($chunk)) as $decision) {
                $school = $chunk[$decision->key] ?? null;
                if (!$school instanceof HarvestedSchool) {
                    continue;   // décision hors périmètre du lot : ignorée
                }
                $baseline['schools'][$decision->key] = [
                    'nom' => $school->name,
                    'nom_canonique' => $decision->canonicalName,
                    'frequence' => $school->frequency,
                    'identifiants_jarvi' => $school->jarviIds,
                    'statut' => 'candidat',
                ];
                ++$report->schoolsGrouped;
            }
        }

        return $this->recount($baseline, $report);
    }

    /**
     * @param array<string, mixed> $baseline
     *
     * @return array<string, mixed>
     */
    private function recount(array $baseline, SchoolGroupingReport $report): array
    {
        $canonical = [];
        /** @var array<string, mixed> $school */
        foreach ($baseline['schools'] as $school) {
            $name = (string) ($school['nom_canonique'] ?? '');
            if ('' !== $name) {
                $canonical[$name] = true;
            }
        }
        $report->canonicalNames = \count($canonical);

        return $baseline;
    }
}
