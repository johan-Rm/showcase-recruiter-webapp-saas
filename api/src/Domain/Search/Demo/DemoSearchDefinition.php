<?php

declare(strict_types=1);

namespace App\Domain\Search\Demo;

/**
 * Une recherche du catalogue de démonstration — task API-029.
 *
 * `criteria` l'IDENTIFIE : le nom est composé automatiquement à partir des critères et vaut le plus
 * souvent la chaîne vide en base, il ne peut donc pas servir de repère d'une exécution à l'autre.
 *
 * `byColleague` fait porter la recherche par un AUTRE recruteur de l'entreprise : c'est le seul
 * moyen d'obtenir la carte en lecture seule, qu'on ne peut pas fabriquer sur sa propre recherche.
 */
final readonly class DemoSearchDefinition
{
    /**
     * @param list<array{facet: string, value: string, group: string}> $criteria
     * @param string                                                   $name     vide = nom composé par l'écran à partir des critères
     */
    public function __construct(
        public array $criteria,
        public string $name = '',
        public bool $byColleague = false,
        public bool $shared = false,
        public bool $closed = false,
    ) {
    }
}
