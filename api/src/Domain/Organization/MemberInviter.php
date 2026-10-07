<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Entity\Invitation;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Enum\OrganizationRole;
use App\Repository\InvitationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Émission d'une invitation à rejoindre une entreprise (cf. data-model.md § Onboarding).
 * Garde-fous V1 : pas de doublon (email déjà membre ou invitation PENDING existante) ; le token
 * généré est aléatoire et non devinable. En V1 l'email réel n'est pas envoyé : le token est
 * retourné/journalisé par la couche appelante.
 */
final readonly class MemberInviter
{
    private const int EXPIRY_DAYS = 14;

    public function __construct(
        private EntityManagerInterface $em,
        private InvitationRepository $invitations,
    ) {
    }

    public function invite(Organization $organization, Recruiter $invitedBy, string $email, OrganizationRole $role): Invitation
    {
        $this->assertNotAlreadyMember($organization, $email);

        if (null !== $this->invitations->findPending($organization, $email)) {
            throw new EmailAlreadyInvitedException();
        }

        $invitation = new Invitation(
            $organization,
            $email,
            $this->generateToken(),
            $invitedBy,
            $role,
            expiresAt: new DateTimeImmutable(\sprintf('+%d days', self::EXPIRY_DAYS)),
        );
        $this->em->persist($invitation);
        $this->em->flush();

        return $invitation;
    }

    private function assertNotAlreadyMember(Organization $organization, string $email): void
    {
        $person = $this->em->getRepository(Person::class)->findOneBy(['contactEmail' => $email]);
        if (null === $person) {
            return;
        }

        if (null !== $this->em->getRepository(Recruiter::class)->findOneBy(['person' => $person, 'organization' => $organization])) {
            throw new EmailAlreadyMemberException();
        }
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(24));
    }
}
