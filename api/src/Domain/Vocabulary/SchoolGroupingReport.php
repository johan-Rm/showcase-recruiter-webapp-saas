<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Compteurs d'une passe de regroupement : établissements soumis, regroupés, et le nombre de noms
 * canoniques distincts obtenus — c'est ce dernier qui dit si le regroupement a vraiment réduit la
 * dispersion (l'égalité de chaîne, mesurée, n'y parvenait pas).
 */
final class SchoolGroupingReport
{
    public int $schoolsSubmitted = 0;
    public int $schoolsGrouped = 0;
    public int $canonicalNames = 0;
}
