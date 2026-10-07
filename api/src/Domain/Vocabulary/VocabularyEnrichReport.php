<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Compteurs d'un enrichissement : ce qui a été soumis et comment le clustering l'a réparti.
 * Sert le retour console et l'assertion de test.
 */
final class VocabularyEnrichReport
{
    public int $termsSubmitted = 0;
    public int $conceptsCreated = 0;
    public int $synonymsAttached = 0;
    public int $noiseDropped = 0;
}
