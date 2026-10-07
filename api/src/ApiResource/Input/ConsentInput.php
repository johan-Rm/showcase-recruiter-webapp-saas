<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Décision de consentement du candidat (self-service, tâche 033). `true` = consentement explicite
 * (horodaté côté serveur, RGPD) ; `false` = retrait du consentement. Le Magic Link authentifie mais
 * ne vaut jamais consentement implicite : cette action est toujours explicite.
 */
final class ConsentInput
{
    #[Assert\NotNull]
    public ?bool $consent = null;
}
