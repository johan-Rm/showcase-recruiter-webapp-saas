<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use DateTimeImmutable;

/**
 * Compte rendu d'une relecture de libellés (task 124). `unknownIds` est renvoyé — un décompte seul
 * laisserait croire que tout est passé (uuid invalide ou libellé absent).
 */
final class LabelReviewOutput
{
    /** @param list<string> $unknownIds */
    public function __construct(
        public string $status,
        public int $reviewed,
        public array $unknownIds,
        public string $reviewedBy,
        public DateTimeImmutable $reviewedAt,
    ) {
    }
}
