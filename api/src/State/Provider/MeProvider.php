<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\MeOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Candidate;
use App\Entity\Recruiter;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * GET /v1/me : utilisateur courant (identité Person + rôles + présence des contextes
 * candidat/recruteur). Le firewall JWT garantit un User authentifié.
 *
 * @implements ProviderInterface<MeOutput>
 */
final readonly class MeProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private CurrentRecruiterResolver $recruiters,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?MeOutput
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $person = $user->getPerson();
        $candidate = $this->em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        $hasRecruiter = null !== $this->em->getRepository(Recruiter::class)->findOneBy(['person' => $person]);
        // La casquette ACTIVE, par le même chemin que les providers de recherche : `recruiter` dit
        // qu'on en a une, `organizationId` dit laquelle on peut encore exercer.
        $recruiter = $this->recruiters->resolveFirstActive($user);

        return new MeOutput(
            $person->getUuid()->toString(),
            $user->getEmail(),
            $person->givenName,
            $person->familyName,
            $user->getRoles(),
            null !== $candidate,
            $hasRecruiter,
            $candidate?->getUuid()->toString(),
            $candidate?->getJarvi()?->syncStatus->value,
            $recruiter?->organization->getUuid()->toString(),
        );
    }
}
