<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Enum\RecruiterStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Deux règles distinctes sur une recherche (task 072) :
 * READ  = l'auteur, OU un membre actif de la même organisation SI la recherche est partagée.
 * WRITE = l'auteur, et lui seul — critères, nom, suppression, favoris, export.
 *
 * Un destinataire d'une recherche partagée est TOUJOURS en lecture seule : pas de co-édition. S'il
 * veut la faire sienne, il la duplique.
 *
 * @extends Voter<self::READ|self::WRITE, SavedSearch>
 */
final class SavedSearchVoter extends Voter
{
    public const string READ = 'SEARCH_READ';
    public const string WRITE = 'SEARCH_WRITE';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::READ, self::WRITE], true) && $subject instanceof SavedSearch;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $recruiter = $this->em->getRepository(Recruiter::class)->findOneBy([
            'person' => $user->getPerson(),
            'organization' => $subject->organization,
            'status' => RecruiterStatus::ACTIVE,
        ]);

        if (!$recruiter instanceof Recruiter || $subject->organization->isRejected()) {
            return false;
        }

        $isOwner = $recruiter->getId() === $subject->owner->getId();

        return match ($attribute) {
            self::READ => $isOwner || $subject->isShared,
            self::WRITE => $isOwner,
        };
    }
}
