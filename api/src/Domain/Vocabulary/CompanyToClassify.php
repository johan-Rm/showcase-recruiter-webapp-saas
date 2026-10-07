<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Une entreprise distincte à classer : sa clé stable (`linkedinCompanyId` si présent, sinon nom
 * normalisé), son nom lisible (canonique si dispo) et sa fréquence (nombre de postes). La clé sert
 * de cache — une entreprise n'est classée qu'une fois.
 */
final readonly class CompanyToClassify
{
    public function __construct(
        public string $key,
        public string $name,
        public int $frequency,
    ) {
    }
}
