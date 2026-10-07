<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Organization\LastOwnerRemovalException;
use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Enum\OrganizationRole;
use App\Enum\RecruiterStatus;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * DELETE /v1/organizations/{id}/members/{recruiterId} : l'owner entreprise retire un membre
 * (voter MANAGE). Retrait = désactivation (status DISABLED) pour préserver l'audit. Interdit de
 * retirer le dernier owner entreprise actif (409). Cf. data-model.md § Onboarding.
 *
 * @implements ProcessorInterface<mixed, null>
 */
final readonly class MemberRemovalProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $organizationId = $uriVariables['id'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $recruiterId = $uriVariables['recruiterId'] ?? null;
        $recruiter = \is_string($recruiterId) && Uuid::isValid($recruiterId)
            ? $this->em->getRepository(Recruiter::class)->findOneBy(['uuid' => Uuid::fromString($recruiterId)])
            : null;
        if (!$recruiter instanceof Recruiter || $recruiter->organization !== $organization) {
            throw new NotFoundHttpException();
        }

        if (OrganizationRole::COMPANY_OWNER === $recruiter->organizationRole && $this->activeCompanyOwnerCount($organization) <= 1) {
            throw new ConflictHttpException((new LastOwnerRemovalException())->getMessage());
        }

        $recruiter->status = RecruiterStatus::DISABLED;
        $this->em->flush();

        return null;
    }

    private function activeCompanyOwnerCount(Organization $organization): int
    {
        return $this->em->getRepository(Recruiter::class)
            ->count([
                'organization' => $organization,
                'organizationRole' => OrganizationRole::COMPANY_OWNER,
                'status' => RecruiterStatus::ACTIVE,
            ]);
    }
}
