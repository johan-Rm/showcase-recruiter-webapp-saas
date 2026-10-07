<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\CandidateDifferentiatorsOutput;
use App\Entity\Candidate;

/**
 * Mapping Candidate → différenciants en sortie plate (tâche 016). Montants en sous-objet
 * `{value, currency}` (cf. CandidateMapper), enums sérialisés en `value`.
 */
final class CandidateDifferentiatorsMapper
{
    public static function toOutput(Candidate $candidate): CandidateDifferentiatorsOutput
    {
        return new CandidateDifferentiatorsOutput(
            $candidate->availability?->value,
            $candidate->returnHorizon?->value,
            $candidate->geoMobility,
            ['value' => $candidate->salaryExpected->amount, 'currency' => $candidate->salaryExpected->currency],
            ['value' => $candidate->salaryMin->amount, 'currency' => $candidate->salaryMin->currency],
        );
    }
}
