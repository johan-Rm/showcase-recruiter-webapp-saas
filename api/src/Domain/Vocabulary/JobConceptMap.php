<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;

/**
 * Référentiel des libellés de postes, chargé UNE fois puis gardé en mémoire (même raison que
 * {@see CompanySectorMap} : une ingestion parcourt des milliers de postes).
 *
 * Les concepts « statut » sont **conservés mais marqués** : c'est {@see TitleConceptResolver} qui
 * les déclasse, afin que « stage auditeur interne » rejoigne Auditeur plutôt que Stage. Les écarter
 * dès ici priverait le résolveur de cet arbitrage.
 *
 * Les libellés sont indexés par mot : rapprocher un intitulé de 355 libellés par force brute coûte
 * des millions de comparaisons, alors que ses propres mots désignent directement les candidats.
 */
final class JobConceptMap implements JobConceptCatalog
{
    /** Marqueur porté par le libellé canonique des concepts qui décrivent une situation, pas un métier. */
    private const string STATUS_MARKER = '(statut)';

    /** En deçà, un libellé est trop court pour être cherché dans un intitulé sans faux rapprochements. */
    private const int MIN_PARTIAL_LENGTH = 4;

    /** @var array<string, JobConceptEntry>|null */
    private ?array $byTerm = null;

    /** @var array<string, list<JobConceptEntry>>|null */
    private ?array $byWord = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, JobConceptEntry> libellé exact → entrée */
    public function byTerm(): array
    {
        $this->load();

        return $this->byTerm ?? [];
    }

    /**
     * Entrées dont l'un des mots apparaît dans l'intitulé — le vivier des correspondances partielles.
     *
     * @param list<string> $words
     *
     * @return list<JobConceptEntry>
     */
    public function candidatesFor(array $words): array
    {
        $this->load();

        $candidates = [];
        foreach ($words as $word) {
            foreach ($this->byWord[$word] ?? [] as $entry) {
                $candidates[$entry->term] = $entry;
            }
        }

        return array_values($candidates);
    }

    private function load(): void
    {
        if (null !== $this->byTerm) {
            return;
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT l.label, l.frequency, c.id AS concept_id, c.pref_label
                FROM vocabulary_label l
                JOIN vocabulary_concept c ON c.id = l.concept_id
                WHERE l.deleted_at IS NULL AND c.deleted_at IS NULL AND c.type = :type
                SQL,
            ['type' => VocabularyType::METIER->value],
        );

        $byTerm = [];
        $byWord = [];
        foreach ($rows as $row) {
            $term = (string) $row['label'];
            $entry = new JobConceptEntry(
                $term,
                (int) $row['concept_id'],
                (int) $row['frequency'],
                str_contains((string) $row['pref_label'], self::STATUS_MARKER),
            );
            $byTerm[$term] = $entry;

            if (mb_strlen($term) >= self::MIN_PARTIAL_LENGTH) {
                foreach (explode(' ', $term) as $word) {
                    $byWord[$word][] = $entry;
                }
            }
        }

        $this->byTerm = $byTerm;
        $this->byWord = $byWord;
    }
}
