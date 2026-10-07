<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Entity\UnresolvedJobTerm;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Uid\Uuid;

/**
 * Garde trace des intitulés de poste sur lesquels on cherche et que le dictionnaire ignore
 * (task API-024).
 *
 * QUAND. Au **lancement** d'une recherche, pas à la frappe. Chaque mot complet est précédé de tous
 * ses débuts, et « d », « dé », « dév » n'apprennent rien sur ce qui manque. Le lancement est un acte
 * d'intention : le recruteur a tapé, il a lu que ça ne ramènerait rien, et il a lancé quand même.
 *
 * ⚠️ Ce n'est PAS `GET /v1/search/candidates` : le formulaire s'en sert comme compteur de volume, à
 * chaque frappe débouncée. Y poser la trace produirait exactement le bruit qu'on veut éviter. Le
 * lancement, côté produit, **crée une recherche enregistrée** — c'est donc `SavedSearchProcessor` qui
 * appelle ici.
 *
 * CE QUI COMPTE COMME INCONNU : un terme que le dictionnaire ne porte sous **aucun** libellé de
 * métier, canonique ou vivant. Un métier qui existe mais que personne n'exerce n'entre pas ici — il
 * ramène zéro lui aussi, mais il dit autre chose (« ce métier existe, le vivier ne l'a pas »), et
 * mélanger les deux rendrait la liste inexploitable.
 *
 * NE FAIT JAMAIS ÉCHOUER UNE RECHERCHE. C'est une observation, pas un résultat : si l'écriture tombe,
 * elle se perd en silence et le recruteur ne voit rien.
 */
final readonly class UnresolvedJobTermRecorder
{
    public function __construct(
        private Connection $connection,
        private SearchCriteriaTranslator $translator,
    ) {
    }

    /**
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    public function recordFrom(array $criteria): void
    {
        // Le terme est extrait par le traducteur et non relu ici : c'est lui qui porte la règle du
        // « dernier gagne » quand deux critères visent la même facette, et la dupliquer garantirait
        // qu'un jour les deux divergent.
        $this->record($this->translator->toSearchCriteria($criteria)->job);
    }

    public function record(?string $term): void
    {
        $typed = mb_substr(trim((string) $term), 0, UnresolvedJobTerm::MAX_LENGTH);
        $key = mb_strtolower($typed);

        if ('' === $key || $this->resolves($key)) {
            return;
        }

        $now = new DateTimeImmutable();

        // Le compteur monte EN BASE : deux recherches simultanées sur le même terme inconnu ne
        // doivent ni créer deux lignes, ni s'écraser l'une l'autre — et un « lire puis écrire » en
        // PHP fait les deux.
        $sql = <<<'SQL'
            INSERT INTO unresolved_job_term (uuid, created_at, updated_at, term, label, occurrences, last_searched_at)
            VALUES (:uuid, :now, :now, :term, :label, 1, :now)
            ON CONFLICT (term) DO UPDATE SET
                occurrences = unresolved_job_term.occurrences + 1,
                last_searched_at = EXCLUDED.last_searched_at,
                updated_at = EXCLUDED.updated_at
            SQL;

        try {
            $this->connection->executeStatement($sql, [
                'uuid' => Uuid::v7()->toString(),
                'now' => $now->format('Y-m-d H:i:s'),
                'term' => $key,
                'label' => $typed,
            ]);
        } catch (DbalException) {
            // Voir le doc-block : une trace qui ne s'écrit pas ne doit rien empêcher.
        }
    }

    /**
     * Le dictionnaire porte-t-il ce métier, sous son libellé canonique ou sous une variante vivante ?
     *
     * Même résolution que le filtre (`CandidateRepository`), à une restriction près : on se limite au
     * type MÉTIER. Un terme qui ne retomberait que sur une compétence n'est pas un métier connu, et le
     * filtre par poste ne le trouvera pas davantage.
     */
    private function resolves(string $term): bool
    {
        $sql = <<<'SQL'
            SELECT 1 FROM vocabulary_concept vc
            WHERE vc.deleted_at IS NULL AND vc.type = :type
              AND (
                LOWER(vc.pref_label) = :term
                OR EXISTS (
                  SELECT 1 FROM vocabulary_label vl
                  WHERE vl.concept_id = vc.id AND vl.deleted_at IS NULL
                    AND vl.status <> :deprecated AND LOWER(vl.label) = :term
                )
              )
            LIMIT 1
            SQL;

        return false !== $this->connection->fetchOne($sql, [
            'type' => VocabularyType::METIER->value,
            'deprecated' => VocabularyStatus::DEPRECIE->value,
            'term' => $term,
        ]);
    }
}
