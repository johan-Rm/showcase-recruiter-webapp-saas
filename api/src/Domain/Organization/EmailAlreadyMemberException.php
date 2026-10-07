<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

/**
 * Levée quand on invite une adresse déjà rattachée comme recruteur à l'entreprise. Cf. data-model.md.
 */
final class EmailAlreadyMemberException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cette adresse est déjà membre de l\'entreprise.');
    }
}
