<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Entity\Invitation;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\InvitationStatus;
use App\Enum\RecruiterStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Acceptation d'une invitation par token (parcours public) : crée de façon atomique le Person,
 * le compte User (rôle entreprise invité, mot de passe hashé) et le Recruiter ACTIVE, puis passe
 * l'invitation à ACCEPTED. Cf. data-model.md § Onboarding.
 */
final readonly class InvitationAcceptor
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function accept(Invitation $invitation, string $rawPassword, string $givenName, string $familyName): Recruiter
    {
        if (!$this->isAcceptable($invitation)) {
            throw new InvitationNotAcceptableException();
        }

        if (null !== $this->em->getRepository(User::class)->findOneBy(['email' => $invitation->email])) {
            throw new EmailAlreadyRegisteredException();
        }

        return $this->em->wrapInTransaction(function () use ($invitation, $rawPassword, $givenName, $familyName): Recruiter {
            $person = new Person($givenName, $familyName, contactEmail: $invitation->email);

            $user = new User($invitation->email, $person);
            $user->setRoles([$invitation->role->value]);
            $user->setPassword($this->passwordHasher->hashPassword($user, $rawPassword));

            $recruiter = new Recruiter(
                $person,
                $invitation->organization,
                $invitation->role,
                status: RecruiterStatus::ACTIVE,
            );

            $this->em->persist($person);
            $this->em->persist($user);
            $this->em->persist($recruiter);

            $invitation->status = InvitationStatus::ACCEPTED;

            return $recruiter;
        });
    }

    private function isAcceptable(Invitation $invitation): bool
    {
        if (InvitationStatus::PENDING !== $invitation->status) {
            return false;
        }

        return null === $invitation->expiresAt || $invitation->expiresAt > new DateTimeImmutable();
    }
}
