<?php

declare(strict_types=1);

namespace App\Enum;

enum Availability: string
{
    case IMMEDIATE = 'IMMEDIATE';
    case NOTICE_1M = 'NOTICE_1M';
    case NOTICE_3M = 'NOTICE_3M';
}
