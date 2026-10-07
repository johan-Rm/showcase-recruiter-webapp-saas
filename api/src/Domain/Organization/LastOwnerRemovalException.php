<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

/**
 * Levée quand on tente de retirer le dernier admin entreprise actif (interdit : laisserait
 * l'organisation sans administrateur). Cf. data-model.md § Onboarding.
 */
final class LastOwnerRemovalException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Impossible de retirer le dernier admin actif de l\'entreprise.');
    }
}
