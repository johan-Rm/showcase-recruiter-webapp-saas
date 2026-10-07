<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordChanger
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private RefreshTokenInvalidator $refreshTokenInvalidator,
    ) {
    }

    public function change(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new InvalidCurrentPasswordException();
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->refreshTokenInvalidator->invalidateFor($user);
        $this->em->flush();
    }
}
