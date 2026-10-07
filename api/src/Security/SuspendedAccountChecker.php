<?php

declare(strict_types=1);

namespace App\Security;

use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\User;
use App\Enum\OrganizationRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Le refus d'une entreprise coupe l'accès de ses recruteurs — à la connexion comme sur les requêtes
 * d'une session déjà ouverte.
 *
 * Ce second cas est la raison d'être du checker : le jeton est signé et porte sa propre échéance,
 * rien ne le révoque côté serveur. Sans point de contrôle à chaque authentification, une session
 * ouverte avant la décision continuerait de travailler jusqu'à expiration.
 *
 * Le statut de l'entreprise reste la seule source : rien n'est recopié sur le compte, et une
 * re-validation rétablit l'accès sans qu'il y ait quoi que ce soit à défaire.
 */
final readonly class SuspendedAccountChecker implements UserCheckerInterface
{
    /**
     * Motif stable plutôt qu'une phrase : l'interface doit distinguer une suspension d'un mot de
     * passe faux pour ne pas envoyer la personne réinitialiser un mot de passe qui n'est pas en
     * cause. Le texte affiché est choisi côté interface, dans la langue de la personne.
     */
    public const string SUSPENDED_MESSAGE = 'account_suspended';

    public function __construct(private CurrentRecruiterResolver $recruiters)
    {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        // Rien avant l'authentification : la suspension se lit sur l'entreprise du recruteur, et le
        // recruteur ne se résout qu'une fois le compte identifié.
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Le test de rôle évite une requête sur chaque appel d'un candidat ou d'un administrateur,
        // qui n'ont pas d'entreprise à interroger.
        if (!$user instanceof User || !$this->belongsToCompany($user)) {
            return;
        }

        $recruiter = $this->recruiters->resolveFirstActive($user);
        if (null !== $recruiter && $recruiter->organization->isRejected()) {
            throw new CustomUserMessageAccountStatusException(self::SUSPENDED_MESSAGE);
        }
    }

    private function belongsToCompany(User $user): bool
    {
        return [] !== array_intersect(
            [OrganizationRole::COMPANY_OWNER->value, OrganizationRole::COMPANY_MEMBER->value],
            $user->getRoles(),
        );
    }
}
