<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Montant en entrée (🌐 MonetaryAmount) : valeur en centimes + devise ISO 4217. Sous-objet plat
 * `{value, currency}`, miroir de la sortie (cf. CandidateOutput). Cf. api-conventions.md.
 */
final class MoneyInput
{
    #[Assert\PositiveOrZero]
    public int $value = 0;

    #[Assert\Length(exactly: 3)]
    public string $currency = 'EUR';
}
