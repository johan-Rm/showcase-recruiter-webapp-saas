<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordSetupToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordSetupToken>
 */
final class PasswordSetupTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordSetupToken::class);
    }

    public function findOneByPlainToken(string $token): ?PasswordSetupToken
    {
        return $this->findOneBy(['tokenHash' => hash('sha256', $token)]);
    }
}
