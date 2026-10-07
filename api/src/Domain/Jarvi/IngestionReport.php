<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use DateTimeImmutable;

/**
 * Résultat d'un passage d'ingestion (tâche 020) : combien de projections créées / mises à jour /
 * ignorées / en erreur, et le curseur `updatedAt` retenu pour le prochain passage incrémental.
 * En cas d'erreur sur un profil, le curseur est figé juste avant (tri `updatedAt` croissant) pour
 * que le passage suivant reprenne le profil fautif au lieu de le sauter (tâche 034).
 */
final readonly class IngestionReport
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $skipped,
        public ?DateTimeImmutable $cursor,
        public bool $dryRun = false,
        public int $errors = 0,
    ) {
    }
}
