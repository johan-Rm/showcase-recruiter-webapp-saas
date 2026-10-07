<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\Sector;

/**
 * Le secteur attribué à une entreprise par la classification. `key` référence la
 * {@see CompanyToClassify} d'origine.
 */
final readonly class CompanySectorDecision
{
    public function __construct(
        public string $key,
        public Sector $sector,
    ) {
    }
}
