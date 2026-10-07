<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DomainException;

final class PasswordResetTokenNotConsumableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Token de réinitialisation invalide ou expiré.');
    }
}
