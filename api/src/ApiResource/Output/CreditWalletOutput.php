<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\CreditWalletProvider;
use DateTimeImmutable;

/**
 * Solde de crédits courant d'une entreprise (lecture membre).
 */
#[ApiResource(
    shortName: 'CreditWallet',
    operations: [
        new Get(
            uriTemplate: '/organizations/{organizationId}/credit-wallet',
            uriVariables: ['organizationId'],
            provider: CreditWalletProvider::class,
        ),
    ],
)]
final class CreditWalletOutput
{
    public function __construct(
        public string $organizationId,
        public int $balance,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
