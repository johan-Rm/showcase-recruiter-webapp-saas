<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

/**
 * Levée quand une invitation n'est plus acceptable (déjà acceptée ou expirée). Mappée en 410 Gone.
 */
final class InvitationNotAcceptableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cette invitation n\'est plus valable.');
    }
}
