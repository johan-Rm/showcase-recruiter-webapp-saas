<?php

declare(strict_types=1);

namespace App\Enum;

enum SyncStatus: string
{
    case PENDING = 'PENDING';
    case SYNCED = 'SYNCED';
    case FAILED = 'FAILED';
}
