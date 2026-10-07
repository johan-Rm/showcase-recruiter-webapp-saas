<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Compte rendu d'un chargement de taxonomie : créations vs mises à jour, concepts et libellés.
 *
 * `labelsRemoved` et `labelsRestored` comptent les DÉTACHEMENTS (task API-035) — un libellé que son
 * concept ne revendique plus dans le fichier, ou qu'il revendique de nouveau. Ce sont les seuls
 * mouvements destructeurs de l'import : ils se lisent séparément pour qu'un retrait massif se
 * remarque avant d'être subi.
 */
final class VocabularyImportReport
{
    public int $conceptsCreated = 0;
    public int $conceptsUpdated = 0;
    public int $labelsCreated = 0;
    public int $labelsUpdated = 0;
    public int $labelsRemoved = 0;
    public int $labelsRestored = 0;
}
