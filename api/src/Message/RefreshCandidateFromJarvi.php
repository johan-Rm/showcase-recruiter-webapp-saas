<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Ordre asynchrone différé : rafraîchir la réplica d'un candidat depuis Jarvi après soumission,
 * le temps que l'extraction asynchrone (~quelques minutes, ordre de grandeur observé) peuple
 * positions/skills (tâche 090). `attempt` borne les tentatives espacées.
 */
final readonly class RefreshCandidateFromJarvi
{
    public function __construct(
        public string $candidateId,
        public int $attempt = 1,
    ) {
    }
}
