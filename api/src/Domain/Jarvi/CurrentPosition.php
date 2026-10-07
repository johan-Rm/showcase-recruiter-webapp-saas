<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Enum\CurrentPositionCertainty;

/**
 * Poste actuel d'un candidat (task 117), avec son degré de certitude.
 *
 * Tout est nul quand la certitude vaut ABSENT : un poste absent ne se traduit pas par un poste vide,
 * et `months` à 0 ne signifie pas « vient d'arriver ».
 */
final readonly class CurrentPosition
{
    public function __construct(
        public CurrentPositionCertainty $certainty,
        public ?string $title = null,
        public ?string $companyName = null,
        /** Concept métier reconnu dans l'intitulé, null si aucun ou si ce n'est qu'un statut. */
        public ?int $conceptId = null,
        /** Mois pleins sur ce poste ; 0 si la date de début manque. */
        public int $months = 0,
    ) {
    }

    public static function absent(): self
    {
        return new self(CurrentPositionCertainty::ABSENT);
    }
}
