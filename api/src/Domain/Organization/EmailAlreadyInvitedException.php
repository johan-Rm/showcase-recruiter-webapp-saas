<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

/**
 * Levée quand une invitation PENDING existe déjà pour ce couple (organization, email) — anti-doublon.
 */
final class EmailAlreadyInvitedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Une invitation est déjà en attente pour cette adresse.');
    }
}
