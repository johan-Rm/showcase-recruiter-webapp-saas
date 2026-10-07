<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefreshTokenInvalidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function invalidateFor(User $user): void
    {
        $this->em->createQueryBuilder()
            ->delete(RefreshToken::class, 'refreshToken')
            ->where('refreshToken.username = :username')
            ->setParameter('username', $user->getUserIdentifier())
            ->getQuery()
            ->execute();
    }
}
