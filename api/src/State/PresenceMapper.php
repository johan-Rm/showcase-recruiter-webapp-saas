<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\PresencePeriodOutput;
use App\Entity\PresencePeriod;

/**
 * Mapping PresencePeriod → Output plat (dates en `Y-m-d`). Cf. CandidateMapper pour le format.
 */
final class PresenceMapper
{
    public static function toOutput(PresencePeriod $period): PresencePeriodOutput
    {
        return new PresencePeriodOutput(
            $period->getUuid()->toString(),
            $period->startDate->format('Y-m-d'),
            $period->endDate->format('Y-m-d'),
            $period->city,
        );
    }
}
