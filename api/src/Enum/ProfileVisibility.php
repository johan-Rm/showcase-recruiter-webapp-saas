<?php

declare(strict_types=1);

namespace App\Enum;

enum ProfileVisibility: string
{
    case VISIBLE = 'VISIBLE';
    case HIDDEN = 'HIDDEN';
    case RESTRICTED = 'RESTRICTED';
    case PREMIUM_ONLY = 'PREMIUM_ONLY';
}
