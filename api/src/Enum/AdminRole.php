<?php

declare(strict_types=1);

namespace App\Enum;

enum AdminRole: string
{
    case ADMIN_OWNER = 'ROLE_ADMIN_OWNER';
    case ADMIN_MEMBER = 'ROLE_ADMIN_MEMBER';
}
