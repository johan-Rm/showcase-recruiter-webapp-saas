<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\AdminRole;
use App\Enum\OrganizationRole;
use App\Enum\RecruiterStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Isolation multi-tenant des entreprises : un recruteur n'accède qu'à son Organization.
 * VIEW   = membre de l'Organization, ou admin en lecture cross-tenant.
 * MANAGE = ROLE_COMPANY_OWNER de l'Organization.
 * ACCESS = membre d'une Organization qui n'a pas été refusée, ou admin en lecture cross-tenant. La
 *          revue ne garde pas la porte : une entreprise encore en revue travaille normalement.
 * Pas de court-circuit global : l'accès admin reste un cas explicite.
 *
 * @extends Voter<self::VIEW|self::MANAGE|self::ACCESS, Organization>
 */
final class OrganizationVoter extends Voter
{
    public const string VIEW = 'ORGANIZATION_VIEW';
    public const string MANAGE = 'ORGANIZATION_MANAGE';
    public const string ACCESS = 'ORGANIZATION_ACCESS';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::MANAGE, self::ACCESS], true) && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->isAdminMember($user)) {
            return self::MANAGE !== $attribute;
        }

        $recruiter = $this->em->getRepository(Recruiter::class)
            ->findOneBy(['person' => $user->getPerson(), 'organization' => $subject]);

        if (null === $recruiter || RecruiterStatus::ACTIVE !== $recruiter->status) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::MANAGE => OrganizationRole::COMPANY_OWNER === $recruiter->organizationRole,
            self::ACCESS => !$subject->isRejected(),
        };
    }

    private function isAdminMember(User $user): bool
    {
        return [] !== array_intersect(
            [AdminRole::ADMIN_MEMBER->value, AdminRole::ADMIN_OWNER->value],
            $user->getRoles(),
        );
    }
}
