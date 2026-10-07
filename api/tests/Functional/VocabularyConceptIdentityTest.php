<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Vocabulary\VocabularyImporter;
use App\Entity\VocabularyConcept;
use App\Enum\VocabularyStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Identité d'un concept à l'import (task 113).
 *
 * Un concept s'identifie par son **code stable** (`competence-001` dans `taxonomy.json`), pas par
 * son libellé. Sans cela, corriger « Ingénieur d'études » en « Ingénieur d'étude » créerait un
 * second concept `candidat` et **orphelinerait le validé** : le travail de relecture humaine serait
 * perdu en silence, et les recherches sauvegardées portant l'ancien libellé casseraient.
 */
final class VocabularyConceptIdentityTest extends ApiTestCase
{
    public function testRenamingAConceptKeepsItsIdentityAndItsValidation(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $importer = self::getContainer()->get(VocabularyImporter::class);
        $code = 'competence-test-'.uniqid();

        $importer->import($this->taxonomy($code, 'Ingénieur d’études', 'VALIDE'));
        $em->clear();

        $before = $em->getRepository(VocabularyConcept::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(VocabularyConcept::class, $before);
        self::assertSame(VocabularyStatus::VALIDE, $before->status);

        // Même code, libellé corrigé : pas de doublon, et la validation survit.
        $importer->import($this->taxonomy($code, 'Ingénieur d’étude', 'VALIDE'));
        $em->clear();

        $matches = $em->getRepository(VocabularyConcept::class)->findBy(['code' => $code]);
        self::assertCount(1, $matches, 'le renommage ne doit jamais créer un second concept');
        self::assertSame('Ingénieur d’étude', $matches[0]->prefLabel, 'le libellé suit le fichier');
        self::assertSame(VocabularyStatus::VALIDE, $matches[0]->status, 'la validation humaine survit');
    }

    /** @return array<string, mixed> */
    private function taxonomy(string $code, string $label, string $statut): array
    {
        return [
            'competences' => ['concepts' => [[
                'id' => $code,
                'label_canonique' => $label,
                'statut' => $statut,
                'frequence_totale' => 10,
                'synonymes' => [['terme' => 'ingenieur etudes '.$code, 'frequence' => 10, 'langue' => 'fr']],
            ]]],
            'postes' => ['concepts' => []],
        ];
    }
}
