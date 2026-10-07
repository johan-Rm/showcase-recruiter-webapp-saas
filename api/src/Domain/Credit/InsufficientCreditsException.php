<?php

declare(strict_types=1);

namespace App\Domain\Credit;

use DomainException;

/**
 * Levée quand le solde de crédits est insuffisant pour un dévoilement
 * (le solde ne peut jamais devenir négatif — cf. data-integrity.md).
 */
final class InsufficientCreditsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Solde de crédits insuffisant pour dévoiler ce profil.');
    }
}
