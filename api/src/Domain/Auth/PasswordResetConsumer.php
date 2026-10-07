<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordResetConsumer
{
    public function __construct(
        private PasswordResetTokenRepository $tokens,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private RefreshTokenInvalidator $refreshTokenInvalidator,
    ) {
    }

    public function consume(string $plainToken, string $newPassword): void
    {
        $token = $this->tokens->findOneByPlainToken($plainToken);
        $now = new DateTimeImmutable();
        if (!$token instanceof PasswordResetToken || !$token->isConsumable($now)) {
            throw new PasswordResetTokenNotConsumableException();
        }

        $token->user->setPassword($this->passwordHasher->hashPassword($token->user, $newPassword));
        $token->consumedAt = $now;
        $this->refreshTokenInvalidator->invalidateFor($token->user);
        $this->em->flush();
    }
}
