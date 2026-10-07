<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Entity\Candidate;
use App\Enum\ProfileVisibility;

/**
 * Règle unique d'exposition recruteur : un profil est actionnable seulement s'il est dans le
 * périmètre, visible, non supprimé et explicitement consenti.
 */
final class CandidateExposurePolicy
{
    public function isExposable(Candidate $candidate): bool
    {
        return $candidate->active
            && !$candidate->isDeleted()
            && ProfileVisibility::VISIBLE === $candidate->visibility
            && $candidate->hasConsent();
    }
}
