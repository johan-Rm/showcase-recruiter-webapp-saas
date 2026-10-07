<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DomainException;

final class InvalidCurrentPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Mot de passe courant invalide.');
    }
}
