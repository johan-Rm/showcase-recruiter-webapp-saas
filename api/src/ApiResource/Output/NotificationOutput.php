<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\State\Processor\NotificationReadProcessor;
use App\State\Provider\NotificationsProvider;
use DateTimeImmutable;

/**
 * Notification in-app du destinataire connecté (tâche 058).
 *
 * Flux à curseur `GET /notifications?after={id}` où `id` est l'UUID (v7, chronologiquement trié —
 * l'id bigint interne n'est jamais exposé, cf. convention AbstractEntity) ; renvoie l'enveloppe
 * `NotificationFeedOutput` (items + `nextCursor`). Anonymisé : aucune identité d'entreprise (« une
 * entreprise a dévoilé votre profil », sans dire laquelle). Marquage lu unitaire ou de masse.
 * Cf. ESPACE_CANDIDAT.md §8-9.
 */
#[ApiResource(
    shortName: 'Notification',
    operations: [
        new Get(
            uriTemplate: '/notifications',
            output: NotificationFeedOutput::class,
            provider: NotificationsProvider::class,
            paginationEnabled: false,
        ),
        new Post(
            uriTemplate: '/notifications/{uuid}/read',
            uriVariables: ['uuid'],
            read: false,
            deserialize: false,
            processor: NotificationReadProcessor::class,
        ),
        new Post(
            uriTemplate: '/notifications/read-all',
            status: 204,
            read: false,
            deserialize: false,
            processor: NotificationReadProcessor::class,
        ),
    ],
)]
final class NotificationOutput
{
    /** @param array<string, mixed>|null $data */
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $type,
        public ?array $data,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $readAt,
    ) {
    }
}
