<?php

declare(strict_types=1);

namespace App\Enum;

enum SubscriptionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PAST_DUE = 'PAST_DUE';
    case CANCELED = 'CANCELED';
}
