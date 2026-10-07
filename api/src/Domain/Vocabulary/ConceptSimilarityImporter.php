<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Entity\VocabularyConcept;
use App\Entity\VocabularyConceptSimilarity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Remplace le jeu de relations de proximité (task 111). Champ dérivé : on recalcule tout plutôt que
 * de réconcilier, sinon une paire tombée sous le seuil resterait en base pour toujours.
 *
 * Purge en SQL AVANT d'insérer : l'UnitOfWork ordonne les INSERT avant les DELETE, une purge via
 * l'ORM violerait donc la contrainte d'unicité sur la paire.
 *
 * ⚠️ **Ne jamais porter d'état humain sur ces lignes** — statut de validation, note, commentaire.
 * Cette purge les effacerait au premier rerun, en silence. La confiance se dérive du CONCEPT
 * (task 113) : les voisins d'un concept validé héritent de sa confiance.
 */
final readonly class ConceptSimilarityImporter
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param list<ConceptSimilarity> $similarities
     *
     * @return int nombre de relations écrites
     */
    public function replaceAll(array $similarities): int
    {
        // Purge + réinsertion dans UNE transaction : sans elle, un échec d'insertion laisse la table
        // VIDE et sans recours — vécu le 2026-07-25, un simple décalage de schéma a effacé
        // 6 729 relations. Le remplacement doit être tout ou rien.
        return $this->entityManager->wrapInTransaction(function () use ($similarities): int {
            $this->entityManager->getConnection()->executeStatement('DELETE FROM vocabulary_concept_similarity');

            foreach ($similarities as $similarity) {
                $this->entityManager->persist(new VocabularyConceptSimilarity(
                    $this->entityManager->getReference(VocabularyConcept::class, $similarity->conceptAId),
                    $this->entityManager->getReference(VocabularyConcept::class, $similarity->conceptBId),
                    $similarity->cooccurrences,
                    $similarity->jaccard,
                    $similarity->npmi,
                ));
            }
            $this->entityManager->flush();

            return \count($similarities);
        });
    }
}
