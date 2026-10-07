<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use Doctrine\DBAL\Connection;

/**
 * Récolte les établissements DISTINCTS des formations depuis la réplica `candidate_jarvi` (LECTURE
 * SEULE), regroupés par **nom normalisé** (task 115).
 *
 * Le regroupement ne passe PAS par l'identifiant Jarvi, contrairement aux entreprises : mesuré sur
 * 4 494 formations, 2 370 noms portent 2 465 identifiants — Jarvi héberge plusieurs entités pour un
 * même établissement (« université privée de fès » en compte 4). L'identifiant **fragmente** ; il est
 * conservé comme référence vers la source, jamais comme clé.
 *
 * La normalisation vient de {@see TermNormalizer}, partagée avec la récolte du vocabulaire : c'est
 * la troisième dimension qui en a besoin, la recopier une fois de plus rouvrirait la divergence que
 * `094a` a fermée.
 *
 * SQL natif → porte sa PROPRE clause `deleted_at IS NULL` (le soft delete Doctrine ne le couvre pas).
 */
final readonly class SchoolHarvester
{
    public function __construct(
        private Connection $connection,
        private TermNormalizer $normalizer,
    ) {
    }

    /** @return list<HarvestedSchool> */
    public function harvest(): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT
                ed->'school'->>'name' AS name,
                ed->'school'->>'id' AS jarvi_id
            FROM candidate_jarvi cj, json_array_elements(cj.educations) AS ed
            WHERE cj.deleted_at IS NULL AND btrim(coalesce(ed->'school'->>'name', '')) <> ''
            SQL);

        // Clés en `array-key` et non `string` : PHP retype en int une clé purement numérique, et il
        // existe des noms d'établissement qui le sont (« 42 »).
        /** @var array<array-key, array{frequency: int, ids: array<string, true>, variants: array<string, int>}> $schools */
        $schools = [];
        foreach ($rows as $row) {
            $key = $this->normalizer->normalize((string) $row['name']);
            if ('' === $key) {
                continue;
            }

            $schools[$key] ??= ['frequency' => 0, 'ids' => [], 'variants' => []];
            ++$schools[$key]['frequency'];
            $variant = trim((string) $row['name']);
            $schools[$key]['variants'][$variant] = ($schools[$key]['variants'][$variant] ?? 0) + 1;
            $jarviId = trim((string) ($row['jarvi_id'] ?? ''));
            if ('' !== $jarviId) {
                $schools[$key]['ids'][$jarviId] = true;
            }
        }

        $harvested = [];
        foreach ($schools as $key => $school) {
            arsort($school['variants']);
            $harvested[] = new HarvestedSchool(
                (string) $key,
                (string) array_key_first($school['variants']),
                $school['frequency'],
                array_keys($school['ids']),
                array_keys($school['variants']),
            );
        }
        usort($harvested, static fn (HarvestedSchool $a, HarvestedSchool $b): int => [$b->frequency, $a->key] <=> [$a->frequency, $b->key]);

        return $harvested;
    }
}
