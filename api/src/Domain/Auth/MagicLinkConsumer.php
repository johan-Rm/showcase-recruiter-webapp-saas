<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Entity\MagicLinkToken;
use App\Entity\Person;
use App\Entity\User;
use App\Repository\MagicLinkTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class MagicLinkConsumer
{
    public function __construct(
        private MagicLinkTokenRepository $tokens,
        private EntityManagerInterface $em,
        private JWTTokenManagerInterface $jwt,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function consume(string $plainToken): MagicLinkAuthenticatedUser
    {
        $token = $this->tokens->findOneByPlainToken($plainToken);
        $now = new DateTimeImmutable();
        if (!$token instanceof MagicLinkToken || !$token->isConsumable($now)) {
            throw new MagicLinkNotConsumableException();
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $token->email])
            ?? $this->createAccount($token->email);

        $token->consumedAt = $now;
        $this->em->flush();

        return new MagicLinkAuthenticatedUser(
            $this->jwt->create($user),
            $user->getEmail(),
            $user->getUuid()->toString(),
        );
    }

    /**
     * Première venue : le compte naît **ici**, pas à la demande du lien — cliquer le lien reçu
     * prouve la possession de la boîte. Aucun rôle : l'inscription qui suit le donnera (entreprise →
     * COMPANY_OWNER à la création de l'entreprise ; candidat → ROLE_CANDIDATE à l'onboarding).
     *
     * L'identité reste vide : le magic link ne capte que l'e-mail, c'est le formulaire d'inscription
     * qui fixe le nom réel du déclarant.
     */
    private function createAccount(string $email): User
    {
        $user = new User($email, new Person('', '', contactEmail: $email));
        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(32))));
        $this->em->persist($user);

        return $user;
    }
}
