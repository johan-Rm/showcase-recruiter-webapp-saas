<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Déclaration d'une période de présence au Maroc (tâche 017). `endDate ≥ startDate` validé ici
 * (422) ; l'intégrité en base (CHECK) suit la convention projet (documentée, migration prod).
 */
final class PresencePeriodInput
{
    #[Assert\NotNull]
    public ?DateTimeImmutable $startDate = null;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
    public ?DateTimeImmutable $endDate = null;

    #[Assert\Length(max: 100)]
    public ?string $city = null;
}
