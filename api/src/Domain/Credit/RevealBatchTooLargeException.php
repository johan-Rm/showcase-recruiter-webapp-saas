<?php

declare(strict_types=1);

namespace App\Domain\Credit;

use DomainException;

/**
 * Lot de dévoilements au-delà du plafond (cf. CreditPolicy::MAX_REVEAL_BATCH).
 *
 * Refus SEC plutôt que troncature : dévoiler les 50 premiers d'une demande de 200 laisserait
 * l'appelant croire que tout est passé, et 50 crédits dépensés sans qu'il l'ait voulu.
 */
final class RevealBatchTooLargeException extends DomainException
{
}
