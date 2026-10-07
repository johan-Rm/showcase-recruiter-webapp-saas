<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Classe les entreprises du vivier dans un secteur (déduit du nom), via le {@see CompanySectorClient}.
 * Étape hors ligne du chantier secteur (task 105) : n'écrit que `sectors.json` (relu en `git diff`),
 * jamais la base. Tout entre en statut `candidat` — la validation reste humaine.
 *
 * Incrémentale : elle lit `sectors.json` pour ne (re)classer que les entreprises absentes (clé =
 * `linkedinCompanyId` / nom normalisé). Le seuil de fréquence borne éventuellement la traîne.
 */
final readonly class CompanySectorClassifier
{
    public function __construct(
        private CompanySectorClient $client,
        private int $minFrequency = 1,
        private int $batchSize = 100,
    ) {
    }

    /**
     * @param list<CompanyToClassify>       $companies récoltées (la récolte reste au périmètre appelant)
     * @param array<string, mixed>          $sectors   contenu existant de sectors.json (déjà décodé)
     * @param (callable(string): void)|null $log
     *
     * @return array<string, mixed> sectors.json enrichi
     */
    public function classify(array $companies, array $sectors, CompanySectorClassifyReport $report, ?callable $log = null): array
    {
        $sectors['companies'] ??= [];

        $pending = [];
        foreach ($companies as $company) {
            if ($company->frequency >= $this->minFrequency && !isset($sectors['companies'][$company->key])) {
                $pending[$company->key] = $company;
            }
        }
        if ([] === $pending) {
            if (null !== $log) {
                $log('Aucune entreprise à classer (toutes déjà classées ou sous le seuil).');
            }

            return $sectors;
        }

        foreach (array_chunk($pending, max(1, $this->batchSize), true) as $index => $chunk) {
            if (null !== $log) {
                $log(\sprintf('Lot %d (%d entreprises)…', $index + 1, \count($chunk)));
            }
            $report->companiesSubmitted += \count($chunk);

            foreach ($this->client->classify(array_values($chunk)) as $decision) {
                $company = $chunk[$decision->key] ?? null;
                if (!$company instanceof CompanyToClassify) {
                    continue;   // décision hors périmètre du lot : ignorée
                }
                $sectors['companies'][$decision->key] = [
                    'name' => $company->name,
                    'sector' => $decision->sector->value,
                    'frequence' => $company->frequency,
                    'statut' => 'candidat',
                ];
                ++$report->companiesClassified;
            }
        }

        return $sectors;
    }
}
