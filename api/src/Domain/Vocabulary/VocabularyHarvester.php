<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;

/**
 * Récolte le vocabulaire depuis la réplica `candidate_jarvi` (LECTURE SEULE) : compétences et
 * intitulés de postes distincts, chacun avec sa fréquence par profils distincts.
 *
 * SQL natif → porte sa PROPRE clause `deleted_at IS NULL` : le filtre de soft delete Doctrine ne
 * couvre pas le SQL natif (cf. data-integrity.md). C'est l'ENTRÉE du pipeline vocabulaire : sa
 * sortie nourrit l'enrichissement LLM hors ligne, jamais dans une boucle d'ingestion.
 */
final readonly class VocabularyHarvester
{
    public function __construct(
        private Connection $connection,
        private TermNormalizer $normalizer,
    ) {
    }

    /** @return list<HarvestedTerm> */
    public function harvest(): array
    {
        return [...$this->harvestSkills(), ...$this->harvestTitles()];
    }

    /**
     * Même normalisation que les intitulés — sans elle, `travail d'équipe` et `travail d’équipe`
     * comptaient pour deux termes distincts et le second échappait à la taxonomie.
     *
     * @return list<HarvestedTerm>
     */
    private function harvestSkills(): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT cj.id AS pid, s AS skill
            FROM candidate_jarvi cj, json_array_elements_text(cj.skills) AS s
            WHERE cj.deleted_at IS NULL AND btrim(s) <> ''
            SQL);

        return $this->aggregate($rows, 'skill', VocabularyType::COMPETENCE, $this->normalizer->normalize(...));
    }

    /**
     * Les intitulés sont canonicalisés côté PHP (le SQL rendrait la coupe aux séparateurs illisible),
     * puis agrégés par nombre de profils distincts.
     *
     * @return list<HarvestedTerm>
     */
    private function harvestTitles(): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT cj.id AS pid, we->>'title' AS title
            FROM candidate_jarvi cj, json_array_elements(cj.work_experiences) AS we
            WHERE cj.deleted_at IS NULL AND btrim(coalesce(we->>'title', '')) <> ''
            SQL);

        return $this->aggregate($rows, 'title', VocabularyType::METIER, $this->normalizer->canonicalizeTitle(...));
    }

    /**
     * @param list<array<string, mixed>> $rows      lignes `pid` + colonne brute
     * @param callable(string): string   $normalize forme retenue comme terme
     *
     * @return list<HarvestedTerm>
     */
    private function aggregate(array $rows, string $column, VocabularyType $type, callable $normalize): array
    {
        /** @var array<string, array<int, true>> $profilesByTerm */
        $profilesByTerm = [];
        foreach ($rows as $row) {
            $term = $normalize((string) $row[$column]);
            if ('' === $term) {
                continue;
            }
            $profilesByTerm[$term][(int) $row['pid']] = true;
        }

        $terms = [];
        foreach ($profilesByTerm as $term => $profiles) {
            $terms[] = new HarvestedTerm($type, $term, \count($profiles));
        }

        return $terms;
    }
}
