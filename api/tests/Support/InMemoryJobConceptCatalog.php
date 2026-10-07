<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Vocabulary\JobConceptCatalog;
use App\Domain\Vocabulary\JobConceptEntry;

/**
 * Vocabulaire des postes tenu en mémoire : permet d'éprouver la règle de reconnaissance et le calcul
 * d'expérience sans base ni fixtures.
 */
final readonly class InMemoryJobConceptCatalog implements JobConceptCatalog
{
    /** @param list<JobConceptEntry> $entries */
    public function __construct(private array $entries)
    {
    }

    public function byTerm(): array
    {
        $byTerm = [];
        foreach ($this->entries as $entry) {
            $byTerm[$entry->term] = $entry;
        }

        return $byTerm;
    }

    public function candidatesFor(array $words): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (JobConceptEntry $entry): bool => [] !== array_intersect(explode(' ', $entry->term), $words),
        ));
    }
}
