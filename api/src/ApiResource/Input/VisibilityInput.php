<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\ProfileVisibility;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Choix de visibilité du candidat (self-service, tâche 018). Le changement horodate le consentement
 * (`visibilityUpdatedAt`). L'exposition réelle est appliquée côté serveur (la recherche ne renvoie
 * que les profils VISIBLE), jamais côté client.
 */
final class VisibilityInput
{
    #[Assert\NotNull]
    public ?ProfileVisibility $visibility = null;
}
