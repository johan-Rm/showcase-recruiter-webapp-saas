<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use App\Entity\Invitation;

/**
 * Envoi du lien d'acceptation d'une invitation entreprise. Le lien crée le compte membre et
 * déclenche la définition du mot de passe ; il ne remplace pas une connexion durable.
 */
final readonly class InvitationEmailSender
{
    public function __construct(
        private TransactionalMailer $mailer,
        private string $appBaseUrl = 'http://localhost:3000',
    ) {
    }

    public function send(Invitation $invitation): void
    {
        $link = \sprintf('%s/invitation/%s', rtrim($this->appBaseUrl, '/'), $invitation->token);
        $company = $invitation->organization->name;

        $this->mailer->send($invitation->email, new TransactionalEmail(
            // L'entreprise nomme l'e-mail, pas nous : le destinataire ne connaît pas encore Shortlist,
            // il connaît la personne qui l'invite. Sans ce nom, le message est une sollicitation de
            // plus.
            subject: \sprintf('%s vous invite à rejoindre son espace Shortlist', $company),
            preheader: \sprintf('%s vous invite sur Shortlist.', $company),
            heading: 'Vous êtes invité·e',
            paragraphs: [
                'Bonjour,',
                \sprintf('%s vous invite à rejoindre son espace entreprise Shortlist. Acceptez l\'invitation, puis définissez votre mot de passe.', $company),
            ],
            cta: new EmailCta('Accepter l\'invitation', $link),
            // Une invitation sans date de fin existe (`expiresAt` est nullable) : la note disparaît
            // alors, plutôt que d'annoncer une échéance en la remplaçant par « la date indiquée » —
            // qui n'était indiquée nulle part.
            note: null !== $invitation->expiresAt
                ? \sprintf('Cette invitation expire le %s.', $invitation->expiresAt->format('d/m/Y à H:i'))
                : null,
            footerNote: 'Si vous n\'attendiez pas cette invitation, vous pouvez ignorer cet e-mail.',
        ));
    }
}
