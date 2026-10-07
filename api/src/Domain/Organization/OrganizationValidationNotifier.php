<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use App\Entity\Organization;
use App\Entity\User;
use App\Enum\OrganizationRole;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Annonce au déclarant que son entreprise a passé la vérification.
 *
 * L'accès était déjà ouvert : ce message ne débloque rien, il ferme une question que la personne
 * pouvait se poser. C'est pour ça qu'il ne porte aucun lien — il n'y a rien à faire en le lisant.
 */
final readonly class OrganizationValidationNotifier
{
    public function __construct(
        private EntityManagerInterface $em,
        private TransactionalMailer $mailer,
    ) {
    }

    public function notify(Organization $organization): void
    {
        $user = $this->owner($organization);
        if (!$user instanceof User) {
            return;
        }

        $this->mailer->send($user->getEmail(), new TransactionalEmail(
            subject: 'Votre entreprise est vérifiée',
            preheader: 'Votre entreprise a passé notre vérification.',
            heading: 'Votre entreprise est vérifiée',
            paragraphs: [
                'Bonjour,',
                'Votre entreprise a passé la vérification de notre équipe. Votre espace Shortlist continue de fonctionner sans changement.',
            ],
        ));
    }

    private function owner(Organization $organization): ?User
    {
        foreach ($organization->getRecruiters() as $recruiter) {
            if (OrganizationRole::COMPANY_OWNER !== $recruiter->organizationRole) {
                continue;
            }

            $user = $this->em->getRepository(User::class)->findOneBy(['person' => $recruiter->getPerson()]);

            return $user instanceof User ? $user : null;
        }

        return null;
    }
}
