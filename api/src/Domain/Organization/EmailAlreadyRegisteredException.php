<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

/**
 * Levée à l'acceptation quand l'email de l'invitation possède déjà un compte (règle V1 :
 * 1 Recruiter = 1 Organization, pas de rattachement d'un compte existant). Cf. data-model.md.
 */
final class EmailAlreadyRegisteredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Un compte existe déjà pour cette adresse.');
    }
}
