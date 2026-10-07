<?php

declare(strict_types=1);

namespace App\Enum;

enum UserStatus: string
{
    case INVITED = 'INVITED';
    case ACTIVE = 'ACTIVE';
    case DISABLED = 'DISABLED';
}
