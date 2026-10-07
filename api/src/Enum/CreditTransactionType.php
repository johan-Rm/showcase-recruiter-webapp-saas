<?php

declare(strict_types=1);

namespace App\Enum;

enum CreditTransactionType: string
{
    case GRANT_FREE = 'GRANT_FREE';
    case PURCHASE = 'PURCHASE';
    case CONSUMPTION = 'CONSUMPTION';
    case REFUND = 'REFUND';
    case ADJUSTMENT = 'ADJUSTMENT';
}
