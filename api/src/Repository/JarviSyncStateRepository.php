<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JarviSyncState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JarviSyncState>
 */
final class JarviSyncStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JarviSyncState::class);
    }

    /**
     * Récupère le curseur du flux donné, ou le crée (non flushé) s'il n'existe pas encore.
     */
    public function getOrCreate(string $name): JarviSyncState
    {
        $state = $this->findOneBy(['name' => $name]);
        if ($state instanceof JarviSyncState) {
            return $state;
        }

        $state = new JarviSyncState($name);
        $this->getEntityManager()->persist($state);

        return $state;
    }
}
