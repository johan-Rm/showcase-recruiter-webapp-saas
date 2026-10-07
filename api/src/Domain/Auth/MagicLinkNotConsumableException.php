<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DomainException;

final class MagicLinkNotConsumableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Magic Link invalide, expire ou deja consomme.');
    }
}
