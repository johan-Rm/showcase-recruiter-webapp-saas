<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Organization;
use App\Enum\OrganizationValidationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Organization>
 */
final class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function findOneByUuid(string $uuid): ?Organization
    {
        if (!Uuid::isValid($uuid)) {
            return null;
        }

        return $this->findOneBy(['uuid' => Uuid::fromString($uuid)]);
    }

    /**
     * File de revue admin : les plus anciennes d'abord — celle qui attend depuis le plus longtemps
     * se traite en premier.
     *
     * @return list<Organization>
     */
    public function findForReview(?OrganizationValidationStatus $status, int $page, int $perPage): array
    {
        /* @var list<Organization> */
        return $this->reviewQueryBuilder($status)
            ->orderBy('o.createdAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countForReview(?OrganizationValidationStatus $status): int
    {
        return (int) $this->reviewQueryBuilder($status)
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function reviewQueryBuilder(?OrganizationValidationStatus $status): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o');

        if (null !== $status) {
            $qb->andWhere('o.validationStatus = :status')->setParameter('status', $status);
        }

        return $qb;
    }
}
