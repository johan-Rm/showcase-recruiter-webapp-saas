<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\NotificationFeedOutput;
use App\ApiResource\Output\NotificationOutput;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * GET /v1/notifications?after={uuid}&limit=N : le flux du destinataire connecté (enveloppe
 * `NotificationFeedOutput` : items + `nextCursor`).
 *
 * Self-only PAR CONSTRUCTION : aucun identifiant en URL, on ne lit que les notifications de
 * `user->getPerson()`. Curseur = uuid v7 (chronologique), tri croissant, borné. Lot vide = 200
 * rapide, `nextCursor` null — le BFF SSE (tâche 059) interroge cette route toutes les ~3-5 s.
 *
 * @implements ProviderInterface<NotificationFeedOutput>
 */
final readonly class NotificationsProvider implements ProviderInterface
{
    private const int LIMIT_DEFAULT = 50;
    private const int LIMIT_MAX = 100;

    public function __construct(
        private Security $security,
        private NotificationRepository $notifications,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): NotificationFeedOutput
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $request = $this->requestStack->getCurrentRequest();
        $after = $this->cursor($request?->query->get('after'));
        $limit = min(
            self::LIMIT_MAX,
            max(1, $request?->query->getInt('limit', self::LIMIT_DEFAULT) ?? self::LIMIT_DEFAULT),
        );

        $notifications = $this->notifications->findForRecipientAfter($user->getPerson(), $after, $limit);
        $items = array_map(
            static fn (Notification $notification): NotificationOutput => new NotificationOutput(
                id: (string) $notification->getUuid(),
                type: $notification->type->value,
                data: $notification->data,
                createdAt: $notification->getCreatedAt(),
                readAt: $notification->readAt,
            ),
            $notifications,
        );

        // Curseur = uuid de la dernière notification du lot (à repasser en `after`) ; null si vide.
        $last = end($notifications);
        $nextCursor = $last instanceof Notification ? (string) $last->getUuid() : null;

        return new NotificationFeedOutput($items, $nextCursor);
    }

    private function cursor(mixed $after): ?Uuid
    {
        if (!\is_string($after) || '' === $after) {
            return null;
        }
        if (!Uuid::isValid($after)) {
            throw new BadRequestHttpException('Curseur `after` invalide.');
        }

        return Uuid::fromString($after);
    }
}
