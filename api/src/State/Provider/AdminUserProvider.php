<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminUserOutput;
use App\ApiResource\Output\CollectionOutput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/users : les comptes tous espaces confondus, réservés aux rôles admin. Garde
 * ROLE_ADMIN explicite. Enveloppe plate paginée ; Person fetch-joinée (nom lu par chaque ligne).
 *
 * @implements ProviderInterface<CollectionOutput<AdminUserOutput>>
 */
final readonly class AdminUserProvider implements ProviderInterface
{
    private const int PER_PAGE_DEFAULT = 20;
    private const int PER_PAGE_MAX = 100;

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $request = $this->requestStack->getCurrentRequest();
        $page = max(1, $request?->query->getInt('page', 1) ?? 1);
        $perPage = min(
            self::PER_PAGE_MAX,
            max(1, $request?->query->getInt('perPage', self::PER_PAGE_DEFAULT) ?? self::PER_PAGE_DEFAULT),
        );

        $query = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->innerJoin('u.person', 'p')->addSelect('p')
            ->orderBy('u.createdAt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        $paginator = new Paginator($query, fetchJoinCollection: false);

        $items = array_map(
            static fn (User $user): AdminUserOutput => new AdminUserOutput(
                $user->getUuid()->toString(),
                $user->getPerson()->givenName,
                $user->getPerson()->familyName,
                $user->getEmail(),
                array_values(array_filter($user->getRoles(), static fn (string $role): bool => 'ROLE_USER' !== $role)),
                $user->status->value,
                $user->getLastLoginAt(),
                $user->getCreatedAt(),
            ),
            array_values(iterator_to_array($paginator)),
        );

        return new CollectionOutput($items, \count($paginator), $page, $perPage);
    }
}
