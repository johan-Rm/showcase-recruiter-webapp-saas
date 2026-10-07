<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use App\Entity\Candidate;
use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Enum\MagicLinkAudience;
use App\Repository\CandidateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class MagicLinkIssuer
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private CandidateRepository $candidates,
        private TransactionalMailer $mailer,
        private string $candidateBaseUrl = 'http://candidat.localhost:8080',
        private string $companyBaseUrl = 'http://entreprise.localhost:8080',
        private int $ttlMinutes = 30,
    ) {
    }

    /**
     * Émet un magic link. L'adresse peut être **inconnue** : c'est le cas nominal d'une inscription
     * (entreprise comme candidat). Aucun compte n'est créé ici — il le sera à la consommation, quand
     * la personne aura prouvé qu'elle possède la boîte (cf. MagicLinkConsumer). Une campagne d'envois
     * sur la route publique ne peut donc pas peupler la table des comptes.
     *
     * `$space` décide de l'espace vers lequel le lien renvoie ; l'URL est **configurée côté serveur**,
     * jamais fournie par l'appelant — une URL de redirection venue du client serait une porte ouverte.
     */
    public function request(string $email, ?MagicLinkAudience $space = null): void
    {
        $normalizedEmail = strtolower(trim($email));
        $user = $this->findOrAttachUser($normalizedEmail);

        $now = new DateTimeImmutable();
        $this->consumePreviousTokens($normalizedEmail, $now);
        $plainToken = bin2hex(random_bytes(32));

        $token = new MagicLinkToken(
            $normalizedEmail,
            hash('sha256', $plainToken),
            $now->modify(\sprintf('+%d minutes', $this->ttlMinutes)),
        );
        $this->em->persist($token);
        $this->em->flush();

        $this->send($normalizedEmail, $plainToken, $space ?? $this->audienceOf($user));
    }

    /** Sans indication de l'appelant : un candidat connu reste sur son espace, sinon entreprise. */
    private function audienceOf(?User $user): MagicLinkAudience
    {
        return null !== $user && \in_array('ROLE_CANDIDATE', $user->getRoles(), true)
            ? MagicLinkAudience::CANDIDAT
            : MagicLinkAudience::ENTREPRISE;
    }

    private function consumePreviousTokens(string $email, DateTimeImmutable $now): void
    {
        $this->em->createQueryBuilder()
            ->update(MagicLinkToken::class, 'token')
            ->set('token.consumedAt', ':now')
            ->where('token.email = :email')
            ->andWhere('token.consumedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();
    }

    private function findOrAttachUser(string $email): ?User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user instanceof User) {
            return $user;
        }

        // Rattachement candidat (tâche 033) : si l'email correspond à une projection déjà importée
        // de Jarvi mais sans compte, on crée le User ROLE_CANDIDATE sur le Person EXISTANT (pas de
        // doublon de projection). Le Magic Link authentifie ; il ne vaut PAS consentement.
        $candidate = $this->candidates->findOneByContactEmail($email);
        if ($candidate instanceof Candidate) {
            return $this->attachCandidate($candidate, $email);
        }

        return null;
    }

    private function attachCandidate(Candidate $candidate, string $email): User
    {
        $person = $candidate->getPerson();
        // Fige l'email de contact Shortlist (le Person importé peut être minimal) pour les
        // rattachements ultérieurs directs par `Person.contactEmail`.
        $person->contactEmail ??= $email;

        $user = new User($email, $person);
        $user->setRoles(['ROLE_CANDIDATE']);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
        $this->em->persist($user);

        return $user;
    }

    private function send(string $recipient, string $plainToken, MagicLinkAudience $audience): void
    {
        $isCandidate = MagicLinkAudience::CANDIDAT === $audience;
        $baseUrl = $isCandidate ? $this->candidateBaseUrl : $this->companyBaseUrl;

        /*
         * Le chemin dépend de l'audience, pas seulement le sous-domaine : l'entreprise atterrit
         * DIRECTEMENT sur son onboarding, qui consomme le token lui-même et affiche un loader le
         * temps de le faire. Passer par un écran d'échange intermédiaire lui montrait une coque
         * d'application complète — header, langue, thème, et un bouton « Se déconnecter » cliquable
         * sur l'écran même qui la connecte — avant de la jeter dans un tout autre univers.
         *
         * Un membre déjà inscrit qui clique un lien n'a rien à faire dans l'onboarding : c'est le
         * middleware qui le renvoie vers son espace, il connaît déjà les rôles. Inutile de dupliquer
         * cet aiguillage ici.
         *
         * Le candidat garde l'écran d'échange : son espace n'a pas de tunnel où atterrir.
         */
        $path = $isCandidate ? '/auth/magic-link' : '/inscription/entreprise';

        $link = \sprintf('%s%s?token=%s', rtrim($baseUrl, '/'), $path, $plainToken);

        $note = \sprintf('Ce lien est valable %d minutes et ne peut être utilisé qu\'une seule fois.', $this->ttlMinutes);
        // Anti-hameçonnage : rassure ET désamorce. Ne rien faire suffit — le lien périme seul, et
        // aucun compte n'a été créé à l'émission (cf. `request()`).
        $footerNote = 'Vous n\'êtes pas à l\'origine de cette demande ? Ignorez simplement cet e-mail : personne ne peut accéder à quoi que ce soit sans lui.';

        /*
         * Le VOCABULAIRE suit l'audience, comme le chemin. On ne dit « connexion » que quand l'accès
         * existe vraiment :
         * - entreprise : le magic link POURSUIT une inscription (le compte n'est pas encore actif,
         *   elle passera par un mot de passe une fois validée). Parler de « connexion » ici rendait le
         *   parcours incohérent — le bouton mène à `/inscription/entreprise`.
         * - candidat : le magic link EST son mode de connexion (pas de mot de passe côté candidat).
         *
         * Brancher par audience ne trahit pas l'existence d'un compte : l'audience vient de l'espace
         * qui a demandé le lien, pas de l'e-mail. Cf. wording, distinction des trois moments du parcours.
         */
        $mail = $isCandidate
            ? new TransactionalEmail(
                subject: 'Votre lien de connexion Shortlist',
                preheader: \sprintf('Votre lien de connexion, valable %d minutes.', $this->ttlMinutes),
                heading: 'Votre lien de connexion',
                paragraphs: [
                    'Bonjour,',
                    'Vous avez demandé un accès à Shortlist avec cette adresse. Cliquez sur le bouton pour continuer :',
                ],
                cta: new EmailCta('Continuer sur Shortlist', $link),
                note: $note,
                footerNote: $footerNote,
            )
            : new TransactionalEmail(
                subject: 'Poursuivez votre inscription sur Shortlist',
                preheader: \sprintf('Reprenez votre inscription, lien valable %d minutes.', $this->ttlMinutes),
                heading: 'Poursuivez votre inscription',
                paragraphs: [
                    'Bonjour,',
                    'Vous avez commencé la création de votre espace entreprise sur Shortlist. Cliquez sur le bouton ci-dessous pour poursuivre votre inscription.',
                ],
                cta: new EmailCta('Continuer mon inscription', $link),
                note: $note,
                footerNote: $footerNote,
            );

        $this->mailer->send($recipient, $mail);
    }
}
