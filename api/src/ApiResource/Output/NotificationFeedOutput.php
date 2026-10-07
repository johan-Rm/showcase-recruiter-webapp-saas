<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * Enveloppe du flux de notifications (tâche 058) : les items du lot + le curseur à repasser en
 * `after` au prochain tour — l'uuid de la dernière notification, `null` si le lot est vide.
 *
 * Distincte de `CollectionOutput` (pagination par page : total/page/perPage) : ici c'est un
 * **curseur**, pas une pagination offset. Le BFF SSE (tâche 059) avance sur `nextCursor`.
 */
final class NotificationFeedOutput
{
    /** @param list<NotificationOutput> $items */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {
    }
}
