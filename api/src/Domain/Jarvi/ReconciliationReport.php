<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Résultat d'une réconciliation de périmètre (tâche 020) : taille du périmètre reçu de Jarvi,
 * projections désactivées (sorties de périmètre — jamais supprimées) et projections réactivées
 * (revenues dans le périmètre).
 */
final readonly class ReconciliationReport
{
    public function __construct(
        public int $perimeterSize,
        public int $deactivated,
        public int $reactivated,
    ) {
    }
}
