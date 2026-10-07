<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\CreditTransactionsProvider;
use DateTimeImmutable;

/**
 * Ligne du journal de crédits (lecture membre). Le solde courant = somme des montants.
 */
#[ApiResource(
    shortName: 'CreditTransaction',
    operations: [
        new Get(
            uriTemplate: '/organizations/{organizationId}/credit-transactions',
            uriVariables: ['organizationId'],
            output: CollectionOutput::class,
            provider: CreditTransactionsProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class CreditTransactionOutput
{
    public function __construct(
        public string $id,
        public string $type,
        public int $amount,
        public int $balanceAfter,
        public ?string $reason,
        public ?string $recruiterId,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
