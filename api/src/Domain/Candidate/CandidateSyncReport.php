<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

/**
 * Bilan d'un passage de synchro montante vers Jarvi (tâche 016) : combien de candidats poussés avec
 * succès, combien en échec (laissés FAILED pour retry au passage suivant).
 */
final readonly class CandidateSyncReport
{
    public function __construct(
        public int $synced = 0,
        public int $failed = 0,
    ) {
    }
}
