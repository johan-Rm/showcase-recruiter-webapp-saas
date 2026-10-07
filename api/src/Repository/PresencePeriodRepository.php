<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PresencePeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PresencePeriod>
 */
final class PresencePeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PresencePeriod::class);
    }

    public function findOneByUuid(string $uuid): ?PresencePeriod
    {
        if (!Uuid::isValid($uuid)) {
            return null;
        }

        return $this->findOneBy(['uuid' => Uuid::fromString($uuid)]);
    }
}
