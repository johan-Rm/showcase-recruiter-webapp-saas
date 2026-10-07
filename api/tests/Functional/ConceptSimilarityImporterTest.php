<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Vocabulary\ConceptSimilarity;
use App\Domain\Vocabulary\ConceptSimilarityImporter;
use App\Entity\VocabularyConcept;
use App\Enum\VocabularyType;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Remplacement des relations de proximité (task 111).
 *
 * Le test qui compte est celui de la transaction : `replaceAll` PURGE avant de réinsérer. Sans
 * transaction, un échec d'insertion laisse la table **vide et sans recours** — vécu le 2026-07-25,
 * un décalage de schéma a effacé 6 729 relations. Le remplacement doit être tout ou rien.
 */
final class ConceptSimilarityImporterTest extends ApiTestCase
{
    public function testReplaceAllSwapsTheWholeSet(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$a, $b, $c] = $this->seedConcepts($em);

        $importer = new ConceptSimilarityImporter($em);
        $importer->replaceAll([new ConceptSimilarity($a, $b, 10, 0.5, 0.6)]);
        self::assertSame(1, $this->rowCount($em));

        // Une seconde passe remplace : l'ancienne paire disparaît, la nouvelle est seule.
        $importer->replaceAll([new ConceptSimilarity($a, $c, 7, 0.4, 0.55)]);
        self::assertSame(1, $this->rowCount($em));
        $pair = $em->getConnection()->fetchNumeric('SELECT concept_a_id, concept_b_id FROM vocabulary_concept_similarity');
        self::assertSame([$a, $c], false !== $pair ? array_map(intval(...), $pair) : []);
    }

    /** La purge ne doit JAMAIS survivre à un échec d'insertion. */
    public function testFailedInsertLeavesThePreviousSetIntact(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$a, $b] = $this->seedConcepts($em);

        $importer = new ConceptSimilarityImporter($em);
        $importer->replaceAll([new ConceptSimilarity($a, $b, 10, 0.5, 0.6)]);
        self::assertSame(1, $this->rowCount($em));

        // Concept inexistant → violation de clé étrangère au flush, après la purge.
        try {
            $importer->replaceAll([new ConceptSimilarity($a, 999_999_999, 5, 0.3, 0.4)]);
            self::fail('l’insertion aurait dû échouer');
        } catch (Throwable) {
            // attendu
        }

        $em->clear();
        self::assertSame(1, $this->rowCount($em), 'la purge doit avoir été annulée avec l’insertion');
    }

    /** @return list<int> identifiants de trois concepts frais */
    private function seedConcepts(EntityManagerInterface $em): array
    {
        $ids = [];
        foreach (['Alpha', 'Beta', 'Gamma'] as $label) {
            $concept = new VocabularyConcept(VocabularyType::COMPETENCE, $label.'-'.uniqid());
            $em->persist($concept);
            $em->flush();
            $ids[] = (int) $concept->getId();
        }

        return $ids;
    }

    private function rowCount(EntityManagerInterface $em): int
    {
        return (int) $em->getConnection()->fetchOne('SELECT count(*) FROM vocabulary_concept_similarity');
    }
}
