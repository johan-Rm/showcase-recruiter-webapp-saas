<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DomainException;

final class PasswordSetupTokenNotConsumableException extends DomainException
{
    public function __construct()
    {
        parent::__construct("Ce lien de définition de mot de passe n'est plus valable.");
    }
}
