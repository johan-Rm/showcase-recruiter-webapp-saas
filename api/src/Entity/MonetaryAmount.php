<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Value object montant (🌐 MonetaryAmount) : valeur en centimes + devise ISO 4217.
 * Embarqué dans les entités (salaires…), cf. data-model.md § Conventions communes.
 */
#[ORM\Embeddable]
final class MonetaryAmount
{
    public function __construct(
        #[ORM\Column(type: 'integer')]
        public readonly int $amount = 0,
        #[ORM\Column(type: 'string', length: 3)]
        public readonly string $currency = 'EUR',
    ) {
    }
}
