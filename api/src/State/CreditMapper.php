<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\CreditTransactionOutput;
use App\ApiResource\Output\CreditWalletOutput;
use App\ApiResource\Output\RevealedProfileOutput;
use App\Entity\CreditTransaction;
use App\Entity\CreditWallet;
use App\Entity\RevealedProfile;

/**
 * Mapping Entity → Output DTO plat pour le cluster crédits / dévoilés.
 */
final class CreditMapper
{
    public static function toWallet(CreditWallet $wallet): CreditWalletOutput
    {
        return new CreditWalletOutput(
            $wallet->getOrganization()->getUuid()->toString(),
            $wallet->balance,
            $wallet->getUpdatedAt(),
        );
    }

    public static function toTransaction(CreditTransaction $transaction): CreditTransactionOutput
    {
        return new CreditTransactionOutput(
            $transaction->getUuid()->toString(),
            $transaction->type->value,
            $transaction->amount,
            $transaction->balanceAfter,
            $transaction->reason,
            $transaction->recruiter?->getUuid()->toString(),
            $transaction->getCreatedAt(),
        );
    }

    public static function toRevealed(RevealedProfile $revealed): RevealedProfileOutput
    {
        return new RevealedProfileOutput(
            $revealed->getUuid()->toString(),
            $revealed->candidate->getUuid()->toString(),
            $revealed->revealedBy->getUuid()->toString(),
            $revealed->type->value,
            $revealed->getCreatedAt(),
        );
    }
}
