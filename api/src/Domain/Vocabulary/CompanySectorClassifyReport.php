<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Compteurs d'une passe de classification : entreprises soumises et classées. Sert le retour console
 * et l'assertion de test.
 */
final class CompanySectorClassifyReport
{
    public int $companiesSubmitted = 0;
    public int $companiesClassified = 0;
}
