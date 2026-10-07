<?php

declare(strict_types=1);

namespace App\Domain\Search\Demo;

/**
 * Le compte rendu d'un amorçage de recherches de démonstration — task API-029.
 */
final readonly class DemoSearchReport
{
    /** @param list<DemoSearchLine> $lines */
    public function __construct(
        public string $ownerEmail,
        public ?string $colleagueEmail,
        public string $organizationName,
        public int $created,
        public int $reused,
        public array $lines,
    ) {
    }
}
