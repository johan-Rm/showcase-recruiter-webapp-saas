<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyType;

/**
 * Port du jugement de clustering : regrouper des termes bruts en concepts (synonymie, langue).
 * L'implémentation par défaut interroge un LLM hors ligne ; les tests fournissent un double
 * déterministe. Le pipeline courant (harvest + import) ne dépend jamais de ce port.
 */
interface VocabularyClusteringClient
{
    /**
     * @param list<TermToCluster> $terms            termes non encore rattachés
     * @param list<string>        $existingConcepts libellés canoniques déjà connus (pour rattacher plutôt que dupliquer)
     *
     * @return list<ClusterDecision> une décision par terme fourni
     */
    public function cluster(VocabularyType $type, array $terms, array $existingConcepts): array;
}
