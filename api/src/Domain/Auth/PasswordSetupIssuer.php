<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use App\Entity\PasswordSetupToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PasswordSetupIssuer
{
    public function __construct(
        private EntityManagerInterface $em,
        private TransactionalMailer $mailer,
        private string $appBaseUrl = 'http://localhost:3000',
        private int $ttlMinutes = 30,
    ) {
    }

    public function issue(User $user): void
    {
        $now = new DateTimeImmutable();
        $this->consumePreviousTokens($user, $now);
        $plainToken = bin2hex(random_bytes(32));

        $token = new PasswordSetupToken(
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
            ->update(PasswordSetupToken::class, 'token')
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
        $link = \sprintf('%s/auth/define-password?token=%s', rtrim($this->appBaseUrl, '/'), $plainToken);

        // Ce message arrive dans la seconde qui suit l'envoi du formulaire, pas au bout d'une revue :
        // le déclarant est encore devant son écran et attend la suite immédiate. La consigne passe
        // donc en premier — il n'y a aucune nouvelle à annoncer, seulement un geste à finir.
        $this->mailer->send($user->getEmail(), new TransactionalEmail(
            subject: 'Définissez votre mot de passe Shortlist',
            preheader: 'Il reste votre mot de passe pour ouvrir votre espace.',
            heading: 'Votre espace est prêt',
            paragraphs: [
                'Bonjour,',
                'Votre inscription est enregistrée. Définissez votre mot de passe pour ouvrir votre espace Shortlist.',
            ],
            cta: new EmailCta('Définir mon mot de passe', $link),
            note: \sprintf('Ce lien expire dans %d minutes.', $this->ttlMinutes),
        ));
    }
}
