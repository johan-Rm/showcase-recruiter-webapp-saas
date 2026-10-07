<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * Enveloppe de collection plate (cf. api-conventions.md) : { items, total, page, perPage }.
 * Pas de Hydra/JSON-LD — le SEO/JSON-LD est géré côté webapp.
 *
 * @template TItem
 */
final class CollectionOutput
{
    /** @param list<TItem> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page = 1,
        public int $perPage = 0,
    ) {
    }
}
