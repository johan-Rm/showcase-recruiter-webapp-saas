<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Entity\PasswordSetupToken;
use App\Repository\PasswordSetupTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordSetupConsumer
{
    public function __construct(
        private PasswordSetupTokenRepository $tokens,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Pose le mot de passe et renvoie l'e-mail du compte : le token à usage unique prouve la
     * possession de la boîte, l'appelant peut donc ouvrir une session dans la foulée (le déclarant
     * n'a pas à ressaisir des identifiants qu'il vient de créer).
     */
    public function consume(string $plainToken, string $rawPassword): string
    {
        $token = $this->tokens->findOneByPlainToken($plainToken);
        $now = new DateTimeImmutable();
        if (!$token instanceof PasswordSetupToken || !$token->isConsumable($now)) {
            throw new PasswordSetupTokenNotConsumableException();
        }

        $token->user->setPassword($this->passwordHasher->hashPassword($token->user, $rawPassword));
        $token->consumedAt = $now;
        $this->em->flush();

        return $token->user->getEmail();
    }
}
