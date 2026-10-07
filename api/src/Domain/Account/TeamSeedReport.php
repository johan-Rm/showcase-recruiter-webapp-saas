<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Bilan d'un amorçage d'équipe (cf. TeamAccountSeeder) : ce qui a été créé vs mis à jour.
 */
final class TeamSeedReport
{
    public bool $organizationCreated = false;
    public int $usersCreated = 0;
    public int $usersUpdated = 0;
    public int $candidatesCreated = 0;
    public int $recruitersCreated = 0;
}
