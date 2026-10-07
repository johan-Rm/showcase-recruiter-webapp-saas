<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\Availability;
use App\Enum\ReturnHorizon;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Édition des différenciants candidat (self-service, tâche 016). Ces champs sont **maîtres côté
 * showcase-recruiter-webapp-saas** (≠ profil pro Jarvi) : disponibilité, horizon de retour, mobilité géo et prétentions.
 * Toute modification reste locale showcase-recruiter-webapp-saas.
 *
 * Hors périmètre ici : présence au Maroc (tâche 017) et visibilité (tâche 018), édités à part.
 */
final class DifferentiatorsInput
{
    public ?Availability $availability = null;

    public ?ReturnHorizon $returnHorizon = null;

    public bool $geoMobility = false;

    #[Assert\Valid]
    public ?MoneyInput $salaryExpected = null;

    #[Assert\Valid]
    public ?MoneyInput $salaryMin = null;
}
