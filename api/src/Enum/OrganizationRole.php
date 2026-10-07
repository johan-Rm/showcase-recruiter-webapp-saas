<?php

declare(strict_types=1);

namespace App\Enum;

enum OrganizationRole: string
{
    case COMPANY_OWNER = 'ROLE_COMPANY_OWNER';
    case COMPANY_MEMBER = 'ROLE_COMPANY_MEMBER';
}
