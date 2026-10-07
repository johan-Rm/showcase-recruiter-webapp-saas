<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

/**
 * Levée quand un Person déjà recruteur tente de créer une seconde entreprise
 * (règle V1 : 1 Recruiter = 1 Organization). Cf. data-model.md.
 */
final class OrganizationAlreadyOwnedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cet utilisateur est déjà rattaché à une entreprise.');
    }
}
