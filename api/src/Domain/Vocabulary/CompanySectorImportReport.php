<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Compteurs d'un import de secteurs : créations, mises à jour, entrées ignorées (clé ou secteur
 * inexploitable). Sert le retour console et l'assertion de test.
 */
final class CompanySectorImportReport
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
}
