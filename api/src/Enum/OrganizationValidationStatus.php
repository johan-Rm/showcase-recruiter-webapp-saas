<?php

declare(strict_types=1);

namespace App\Enum;

enum OrganizationValidationStatus: string
{
    case NOUVEAU = 'NOUVEAU';
    case EMAIL_VERIFIE = 'EMAIL_VERIFIE';
    case INCOMPLET = 'INCOMPLET';
    case EN_REVUE = 'EN_REVUE';
    case REFUSE = 'REFUSE';
    case VALIDEE = 'VALIDEE';
}
