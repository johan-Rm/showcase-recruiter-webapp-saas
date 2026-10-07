<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

/**
 * L'état du consentement sur le vivier — task API-032. Il sert la commande d'amorçage, qui doit dire
 * ce qu'elle trouve avant de dire ce qu'elle a fait.
 */
final readonly class DemoConsentCensus
{
    public function __construct(
        public int $total,
        /** Consentis, toutes sources confondues. */
        public int $consented,
        /** Parmi eux, ceux ouverts pour la démonstration — les seuls que la révocation refermera. */
        public int $demonstration,
    ) {
    }

    /** Consentis par un geste réel : ceux qu'aucune commande d'amorçage ne doit toucher. */
    public function genuine(): int
    {
        return $this->consented - $this->demonstration;
    }
}
