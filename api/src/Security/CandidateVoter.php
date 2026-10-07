<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Candidate;
use App\Entity\User;
use App\Enum\AdminRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Self-service candidat (tâche 015) : un candidat n'agit que sur SA propre projection
 * (`candidate.person === user.person`). Un admin passe explicitement. Utilisé par l'édition profil /
 * présence / visibilité (tâches 016/017/018).
 *
 * @extends Voter<self::EDIT, Candidate>
 */
final class CandidateVoter extends Voter
{
    public const string EDIT = 'CANDIDATE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof Candidate;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ([] !== array_intersect(
            [AdminRole::ADMIN_MEMBER->value, AdminRole::ADMIN_OWNER->value],
            $user->getRoles(),
        )) {
            return true;
        }

        $ownerId = $subject->getPerson()->getId();

        return null !== $ownerId && $ownerId === $user->getPerson()->getId();
    }
}
