<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PasswordResetIssuer
{
    public function __construct(
        private EntityManagerInterface $em,
        private TransactionalMailer $mailer,
        private string $appBaseUrl = 'http://localhost:3000',
        private int $ttlMinutes = 30,
    ) {
    }

    public function request(string $email): void
    {
        $normalizedEmail = strtolower(trim($email));
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $normalizedEmail]);
        if (!$user instanceof User) {
            return;
        }

        $now = new DateTimeImmutable();
        $this->consumePreviousTokens($user, $now);
        $plainToken = bin2hex(random_bytes(32));
        $token = new PasswordResetToken(
            $user,
            hash('sha256', $plainToken),
            $now->modify(\sprintf('+%d minutes', $this->ttlMinutes)),
        );
        $this->em->persist($token);
        $this->em->flush();

        $this->send($user, $plainToken);
    }

    private function consumePreviousTokens(User $user, DateTimeImmutable $now): void
    {
        $this->em->createQueryBuilder()
            ->update(PasswordResetToken::class, 'token')
            ->set('token.consumedAt', ':now')
            ->where('token.user = :user')
            ->andWhere('token.consumedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    private function send(User $user, string $plainToken): void
    {
        $link = \sprintf('%s/auth/reset-password?token=%s', rtrim($this->appBaseUrl, '/'), $plainToken);

        $this->mailer->send($user->getEmail(), new TransactionalEmail(
            subject: 'Réinitialiser votre mot de passe Shortlist',
            preheader: 'Choisissez un nouveau mot de passe Shortlist.',
            heading: 'Réinitialisez votre mot de passe',
            paragraphs: [
                'Bonjour,',
                'Vous avez demandé à réinitialiser votre mot de passe Shortlist. Choisissez-en un nouveau avec le bouton ci-dessous.',
            ],
            cta: new EmailCta('Choisir un nouveau mot de passe', $link),
            note: \sprintf('Ce lien expire dans %d minutes.', $this->ttlMinutes),
            // Cet e-mail-ci part sur simple saisie d'une adresse : il atteint donc, par construction,
            // des gens qui n'ont rien demandé. Dire que le mot de passe reste inchangé leur évite
            // d'ouvrir le lien « pour vérifier » — le seul geste qui les mettrait vraiment en risque.
            footerNote: 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail : votre mot de passe reste inchangé.',
        ));
    }
}
