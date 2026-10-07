<?php

declare(strict_types=1);

namespace App\Domain\Email;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Seul point d'envoi des e-mails transactionnels : il possède la MÉCANIQUE — gabarits, parties
 * texte et HTML — et les émetteurs possèdent le TEXTE. Avant lui, les quatre recopiaient le même bloc
 * et n'envoyaient que du texte brut ; le gabarit n'était rendu que par le banc d'essai de dev.
 *
 * L'expéditeur n'est pas ici : `framework.mailer.headers.from` le pose sur tout message qui n'en a
 * pas. Une adresse par émetteur était justement le défaut d'avant — quatre copies à corriger le jour
 * où le domaine d'envoi change.
 */
final readonly class TransactionalMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
    ) {
    }

    public function send(string $recipient, TransactionalEmail $mail): void
    {
        $this->mailer->send($this->compose($mail, new Address($recipient)));
    }

    /**
     * Le message COMPOSÉ, sans l'envoyer. Extrait de `send()` pour la notification de déploiement,
     * qui a besoin du message et pas de l'envoi : le mot de passe du serveur de messagerie vit sur la
     * machine de déploiement, jamais dans cette image — en préproduction, l'expéditeur de
     * l'application est détourné vers un attrape-mails pour qu'aucun message n'atteigne de vraies
     * personnes (cf. `API-065`).
     *
     * L'expéditeur n'est pas posé ici : `framework.mailer.headers.from` s'en charge à l'envoi. Un
     * appelant qui sérialise le message sans l'envoyer doit donc le poser lui-même, sinon le message
     * refuse de se rendre — il n'y a pas d'e-mail sans expéditeur.
     */
    public function compose(TransactionalEmail $mail, Address ...$recipients): Email
    {
        $context = $mail->toContext();

        $email = (new Email())
            ->to(...$recipients)
            ->subject($mail->subject)
            // La partie texte n'est pas décorative : un message sans elle perd des points auprès des
            // filtres anti-spam, et c'est elle que lisent les clients qui refusent le HTML.
            ->text($this->twig->render('emails/transactional.txt.twig', $context));

        $email->html($this->twig->render('emails/transactional.html.twig', $context));

        return $email;
    }
}
