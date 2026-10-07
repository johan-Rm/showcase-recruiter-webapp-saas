<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminDashboardOutput;
use App\Entity\Candidate;
use App\Entity\RevealedProfile;
use App\Entity\User;
use App\Enum\OrganizationValidationStatus;
use App\Repository\CandidateRepository;
use App\Repository\OrganizationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/dashboard : les compteurs de la home admin, calculés à la volée. Garde ROLE_ADMIN
 * explicite (accès cross-tenant). Fenêtre glissante de 7 jours pour l'activité.
 *
 * @implements ProviderInterface<AdminDashboardOutput>
 */
final readonly class AdminDashboardProvider implements ProviderInterface
{
    private const string WINDOW = '-7 days';

    public function __construct(
        private EntityManagerInterface $em,
        private OrganizationRepository $organizations,
        private CandidateRepository $candidates,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminDashboardOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $since = new DateTimeImmutable(self::WINDOW);
        $stats = $this->candidates->vivierStats();

        return new AdminDashboardOutput(
            $this->organizations->countForReview(OrganizationValidationStatus::EN_REVUE),
            $this->countSince(User::class, 'u', 'u.lastLoginAt', $since),
            $this->countSince(RevealedProfile::class, 'r', 'r.createdAt', $since),
            $this->countSince(Candidate::class, 'c', 'c.createdAt', $since),
            $stats['total'],
            $stats['active'],
        );
    }

    /** Compte les lignes dont `$field` tombe dans la fenêtre (soft-delete exclus par le filtre ORM). */
    private function countSince(string $entity, string $alias, string $field, DateTimeImmutable $since): int
    {
        return (int) $this->em
            ->createQuery(\sprintf(
                'SELECT COUNT(%s.id) FROM %s %s WHERE %s >= :since',
                $alias,
                $entity,
                $alias,
                $field,
            ))
            ->setParameter('since', $since)
            ->getSingleScalarResult();
    }
}
