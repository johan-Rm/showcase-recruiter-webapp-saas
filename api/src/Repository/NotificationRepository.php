<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Person;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Notification>
 */
final class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findOneByUuid(string $uuid): ?Notification
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Flux à curseur : les notifications du destinataire postérieures à `$after` (uuid v7,
     * chronologiquement trié — l'id bigint interne n'est jamais exposé), triées croissant et bornées.
     * Sert l'index (recipient_id, uuid) ; réponse vide = requête rapide (le BFF SSE poll souvent).
     *
     * @return list<Notification>
     */
    public function findForRecipientAfter(Person $recipient, ?Uuid $after, int $limit): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :recipient')->setParameter('recipient', $recipient)
            ->andWhere('n.deletedAt IS NULL')
            ->orderBy('n.uuid', 'ASC')
            ->setMaxResults($limit);

        if ($after instanceof Uuid) {
            $qb->andWhere('n.uuid > :after')->setParameter('after', $after, 'uuid');
        }

        return $qb->getQuery()->getResult();
    }

    /** Marque lues toutes les notifications non lues du destinataire ; retourne le nombre affecté. */
    public function markAllReadForRecipient(Person $recipient, DateTimeImmutable $readAt): int
    {
        return (int) $this->createQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':readAt')->setParameter('readAt', $readAt)
            ->andWhere('n.recipient = :recipient')->setParameter('recipient', $recipient)
            ->andWhere('n.readAt IS NULL')
            ->andWhere('n.deletedAt IS NULL')
            ->getQuery()->execute();
    }
}
