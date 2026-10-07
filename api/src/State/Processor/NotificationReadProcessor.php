<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Output\NotificationOutput;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Marquage lu, self-only (tâche 058) :
 *  - POST /v1/notifications/{uuid}/read → marque UNE notification ;
 *  - POST /v1/notifications/read-all    → marque toutes les siennes (204).
 *
 * Une notification qui n'est pas la sienne renvoie 404 (son existence ne se déduit pas), pas 403.
 * Le marquage est idempotent : re-marquer une notification déjà lue ne bouge pas `readAt`.
 *
 * @implements ProcessorInterface<mixed, NotificationOutput|null>
 */
final readonly class NotificationReadProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private NotificationRepository $notifications,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?NotificationOutput
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }
        $recipient = $user->getPerson();

        $uuid = $uriVariables['uuid'] ?? null;

        // Marquage de masse : POST /notifications/read-all (pas d'uuid en URL) → 204.
        if (!\is_string($uuid)) {
            $this->notifications->markAllReadForRecipient($recipient, new DateTimeImmutable());

            return null;
        }

        $notification = $this->notifications->findOneByUuid($uuid);
        if (!$notification instanceof Notification
            || $notification->recipient->getId() !== $recipient->getId()) {
            throw new NotFoundHttpException();
        }

        if (null === $notification->readAt) {
            $notification->readAt = new DateTimeImmutable();
            $this->em->flush();
        }

        return new NotificationOutput(
            id: (string) $notification->getUuid(),
            type: $notification->type->value,
            data: $notification->data,
            createdAt: $notification->getCreatedAt(),
            readAt: $notification->readAt,
        );
    }
}
