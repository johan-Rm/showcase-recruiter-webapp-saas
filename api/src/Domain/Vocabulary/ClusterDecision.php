<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyClusterAction;

/**
 * Ce que le clustering a décidé pour un terme : l'action, le libellé canonique du concept visé
 * (concept existant pour ATTACH, nouveau libellé pour NEW, vide pour NOISE) et la langue détectée.
 */
final readonly class ClusterDecision
{
    public function __construct(
        public string $term,
        public VocabularyClusterAction $action,
        public string $conceptLabel,
        public string $lang,
    ) {
    }
}
