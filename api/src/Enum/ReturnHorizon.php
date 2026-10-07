<?php

declare(strict_types=1);

namespace App\Enum;

enum ReturnHorizon: string
{
    case M3 = 'M3';
    case M6 = 'M6';
    case Y1 = 'Y1';
    case THINKING = 'THINKING';
}
