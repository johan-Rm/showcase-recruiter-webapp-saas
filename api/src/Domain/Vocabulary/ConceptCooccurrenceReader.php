<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;

/**
 * Reconstruit, pour chaque profil, l'ensemble des CONCEPTS qu'il porte (task 111) — la matière
 * première de la co-occurrence.
 *
 * La taxonomie ne référence aucun candidat : le lien se refait ici, en rapprochant les termes bruts
 * de la réplica des libellés de `vocabulary_label`. Le rapprochement passe par {@see TermNormalizer},
 * la MÊME règle que la récolte : une normalisation locale ferait manquer des rattachements.
 *
 * Compétences et intitulés sont cloisonnés par type — « comptable » comme poste et « comptabilité »
 * comme compétence sont deux concepts, un terme ne doit pas traverser les sacs.
 *
 * SQL natif → porte sa PROPRE clause `deleted_at IS NULL` (cf. data-integrity.md).
 */
final readonly class ConceptCooccurrenceReader
{
    public function __construct(
        private Connection $connection,
        private TermNormalizer $normalizer,
    ) {
    }

    /**
     * @return list<list<int>> un tableau d'identifiants de concepts par profil qui en porte au moins un
     */
    public function conceptsByProfile(): array
    {
        $labels = $this->labelsByType();

        /** @var array<int, array<int, true>> $byProfile */
        $byProfile = [];
        foreach ($this->skills() as $row) {
            $term = $this->normalizer->normalize((string) $row['skill']);
            $conceptId = $labels[VocabularyType::COMPETENCE->value][$term] ?? null;
            if (null !== $conceptId) {
                $byProfile[(int) $row['pid']][$conceptId] = true;
            }
        }
        foreach ($this->titles() as $row) {
            $term = $this->normalizer->canonicalizeTitle((string) $row['title']);
            $conceptId = $labels[VocabularyType::METIER->value][$term] ?? null;
            if (null !== $conceptId) {
                $byProfile[(int) $row['pid']][$conceptId] = true;
            }
        }

        return array_values(array_map(
            static fn (array $concepts): array => array_keys($concepts),
            $byProfile,
        ));
    }

    /** @return array<string, array<string, int>> type → libellé normalisé → identifiant de concept */
    private function labelsByType(): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT l.label, c.type, c.id AS concept_id
            FROM vocabulary_label l
            JOIN vocabulary_concept c ON c.id = l.concept_id
            WHERE l.deleted_at IS NULL AND c.deleted_at IS NULL
            SQL);

        $labels = [];
        foreach ($rows as $row) {
            $labels[(string) $row['type']][$this->normalizer->normalize((string) $row['label'])] = (int) $row['concept_id'];
        }

        return $labels;
    }

    /** @return list<array<string, mixed>> */
    private function skills(): array
    {
        return $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT cj.id AS pid, s AS skill
            FROM candidate_jarvi cj, json_array_elements_text(cj.skills) AS s
            WHERE cj.deleted_at IS NULL AND btrim(s) <> ''
            SQL);
    }

    /** @return list<array<string, mixed>> */
    private function titles(): array
    {
        return $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT cj.id AS pid, we->>'title' AS title
            FROM candidate_jarvi cj, json_array_elements(cj.work_experiences) AS we
            WHERE cj.deleted_at IS NULL AND btrim(coalesce(we->>'title', '')) <> ''
            SQL);
    }
}
