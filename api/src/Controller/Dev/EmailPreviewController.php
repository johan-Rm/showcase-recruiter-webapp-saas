<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Banc d'essai du gabarit `emails/transactional` : le rendre au navigateur ou l'envoyer à Mailpit,
 * sans rejouer le parcours qui l'émet (une inscription entreprise complète pour relire un bouton).
 *
 * L'envoi passe par le VRAI `TransactionalMailer`, jamais par une copie de sa mécanique : un banc
 * d'essai qui reconstruit le message à sa façon finit par prouver que SA version marche.
 *
 * `#[When('dev')]` retire la classe du conteneur hors dev ; les routes étant chargées depuis les
 * services contrôleurs (`resource: routing.controllers`), la route disparaît avec elle — un envoi
 * d'e-mail à une adresse arbitraire ne doit pas être joignable en préprod ni en prod.
 */
#[When('dev')]
final readonly class EmailPreviewController
{
    public function __construct(
        private Environment $twig,
        private TransactionalMailer $mailer,
    ) {
    }

    #[Route('/_dev/emails/{sample}', name: 'dev_email_preview', methods: ['GET'])]
    public function __invoke(Request $request, string $sample = 'magic-link'): Response
    {
        $samples = $this->samples();
        if (!isset($samples[$sample])) {
            throw new NotFoundHttpException(\sprintf('Échantillon « %s » inconnu. Disponibles : %s.', $sample, implode(', ', array_keys($samples))));
        }

        $mail = $samples[$sample];

        $recipient = $request->query->getString('send');
        if ('' !== $recipient) {
            $this->mailer->send($recipient, $mail);

            return new JsonResponse(['sent' => true, 'to' => $recipient, 'subject' => $mail->subject]);
        }

        if ('txt' === $request->query->getString('format')) {
            return new Response(
                $this->twig->render('emails/transactional.txt.twig', $mail->toContext()),
                Response::HTTP_OK,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        return new Response(
            $this->twig->render('emails/transactional.html.twig', $mail->toContext()),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }

    /**
     * Décors calqués sur les quatre e-mails réellement émis (MagicLinkIssuer, PasswordSetupIssuer,
     * PasswordResetIssuer, InvitationEmailSender). Le texte fait foi CHEZ L'ÉMETTEUR, pas ici : ces
     * copies dérivent, et c'est le prix assumé pour relire un gabarit sans base de données.
     *
     * @return array<string, TransactionalEmail>
     */
    private function samples(): array
    {
        $token = str_repeat('a1b2c3d4', 8);

        return [
            // Décor de l'audience ENTREPRISE : le magic link poursuit une inscription (cf.
            // MagicLinkIssuer, qui branche connexion/inscription sur l'audience).
            'magic-link' => new TransactionalEmail(
                subject: 'Poursuivez votre inscription sur Shortlist',
                preheader: 'Reprenez votre inscription, lien valable 30 minutes.',
                heading: 'Poursuivez votre inscription',
                paragraphs: [
                    'Bonjour,',
                    'Vous avez commencé la création de votre espace entreprise sur Shortlist. Cliquez sur le bouton ci-dessous pour poursuivre votre inscription.',
                ],
                cta: new EmailCta('Continuer mon inscription', \sprintf('http://entreprise.localhost:8080/inscription/entreprise?token=%s', $token)),
                note: 'Ce lien est valable 30 minutes et ne peut être utilisé qu\'une seule fois.',
                footerNote: 'Vous n\'êtes pas à l\'origine de cette demande ? Ignorez simplement cet e-mail : personne ne peut accéder à quoi que ce soit sans lui.',
            ),
            'password-setup' => new TransactionalEmail(
                subject: 'Votre entreprise est validée, définissez votre mot de passe',
                preheader: 'Votre entreprise est validée : il reste votre mot de passe.',
                heading: 'Votre entreprise est validée',
                paragraphs: [
                    'Bonjour,',
                    'Votre entreprise a été validée par notre équipe. Définissez votre mot de passe pour ouvrir votre espace Shortlist.',
                ],
                cta: new EmailCta('Définir mon mot de passe', \sprintf('http://entreprise.localhost:8080/auth/define-password?token=%s', $token)),
                note: 'Ce lien expire dans 30 minutes.',
            ),
            'password-reset' => new TransactionalEmail(
                subject: 'Réinitialiser votre mot de passe Shortlist',
                preheader: 'Choisissez un nouveau mot de passe Shortlist.',
                heading: 'Réinitialisez votre mot de passe',
                paragraphs: [
                    'Bonjour,',
                    'Vous avez demandé à réinitialiser votre mot de passe Shortlist. Choisissez-en un nouveau avec le bouton ci-dessous.',
                ],
                cta: new EmailCta('Choisir un nouveau mot de passe', \sprintf('http://entreprise.localhost:8080/auth/reset-password?token=%s', $token)),
                note: 'Ce lien expire dans 30 minutes.',
                footerNote: 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail : votre mot de passe reste inchangé.',
            ),
            'invitation' => new TransactionalEmail(
                subject: 'Atlas Recrutement vous invite à rejoindre son espace Shortlist',
                preheader: 'Atlas Recrutement vous invite sur Shortlist.',
                heading: 'Vous êtes invité·e',
                paragraphs: [
                    'Bonjour,',
                    'Atlas Recrutement vous invite à rejoindre son espace entreprise Shortlist. Acceptez l\'invitation, puis définissez votre mot de passe.',
                ],
                cta: new EmailCta('Accepter l\'invitation', \sprintf('http://entreprise.localhost:8080/invitation/%s', $token)),
                note: 'Cette invitation expire le 24/07/2026 à 10:00.',
                footerNote: 'Si vous n\'attendiez pas cette invitation, vous pouvez ignorer cet e-mail.',
            ),
        ];
    }
}
