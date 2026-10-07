<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use DateTimeImmutable;

/**
 * Compte rendu d'une relecture (tâche 120). `unknownCodes` est renvoyé plutôt que tu : un
 * décompte seul laisserait croire que tout est passé.
 */
final class ReferentialReviewOutput
{
    /** @param list<string> $unknownCodes */
    public function __construct(
        public string $status,
        public int $reviewed,
        public array $unknownCodes,
        public string $reviewedBy,
        public DateTimeImmutable $reviewedAt,
    ) {
    }
}
