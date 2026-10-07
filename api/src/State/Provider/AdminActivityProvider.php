<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminActivityOutput;
use App\ApiResource\Output\CollectionOutput;
use App\Doctrine\Filter\SoftDeleteFilter;
use App\Entity\ProfileView;
use App\Entity\RevealedProfile;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/activity : qui a regardé qui, du plus récent au plus ancien. Garde ROLE_ADMIN
 * explicite. Enveloppe plate paginée ; personnes et organisation fetch-joinées (chaque ligne les lit).
 *
 * @implements ProviderInterface<CollectionOutput<AdminActivityOutput>>
 */
final readonly class AdminActivityProvider implements ProviderInterface
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

        // Une trace historique ne disparaît pas parce qu'un compte a été fermé : le soft delete
        // global écarterait la visite entière dès que le recruteur, son entreprise ou le candidat
        // est supprimé. Le filtre documente lui-même ce cas — l'administration voit le supprimé.
        // Les visites supprimées, elles, restent écartées (clause explicite ci-dessous).
        $filters = $this->em->getFilters();
        $wasEnabled = $filters->isEnabled(SoftDeleteFilter::NAME);
        if ($wasEnabled) {
            $filters->disable(SoftDeleteFilter::NAME);
        }

        try {
            return $this->feed($page, $perPage);
        } finally {
            if ($wasEnabled) {
                $filters->enable(SoftDeleteFilter::NAME);
            }
        }
    }

    /** @return CollectionOutput<AdminActivityOutput> */
    private function feed(int $page, int $perPage): CollectionOutput
    {
        $query = $this->em->getRepository(ProfileView::class)->createQueryBuilder('v')
            ->innerJoin('v.candidate', 'c')->addSelect('c')
            ->innerJoin('c.person', 'cp')->addSelect('cp')
            ->innerJoin('v.organization', 'o')->addSelect('o')
            ->innerJoin('v.recruiter', 'r')->addSelect('r')
            ->innerJoin('r.person', 'rp')->addSelect('rp')
            ->where('v.deletedAt IS NULL')
            ->orderBy('v.createdAt', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        $paginator = new Paginator($query, fetchJoinCollection: false);
        $views = array_values(iterator_to_array($paginator));
        $revealed = $this->revealedPairs($views);

        $items = array_map(
            static function (ProfileView $view) use ($revealed): AdminActivityOutput {
                $recruiterPerson = $view->recruiter->getPerson();
                $candidatePerson = $view->candidate->getPerson();

                return new AdminActivityOutput(
                    $view->getUuid()->toString(),
                    $view->getCreatedAt(),
                    trim($recruiterPerson->givenName.' '.$recruiterPerson->familyName),
                    $recruiterPerson->contactEmail,
                    $view->organization->name,
                    $view->candidate->getUuid()->toString(),
                    trim($candidatePerson->givenName.' '.$candidatePerson->familyName),
                    isset($revealed[$view->organization->getId().':'.$view->candidate->getId()]),
                );
            },
            $views,
        );

        return new CollectionOutput($items, \count($paginator), $page, $perPage);
    }

    /**
     * Les dévoilements de la page en UNE requête : demander « ce candidat est-il dévoilé ? » ligne
     * par ligne ferait vingt allers-retours pour vingt lignes.
     *
     * @param list<ProfileView> $views
     *
     * @return array<string, true> clés `organizationId:candidateId`
     */
    private function revealedPairs(array $views): array
    {
        if ([] === $views) {
            return [];
        }

        $rows = $this->em->getRepository(RevealedProfile::class)->createQueryBuilder('rp')
            ->select('IDENTITY(rp.organization) AS organizationId', 'IDENTITY(rp.candidate) AS candidateId')
            ->where('rp.organization IN (:organizations)')
            ->andWhere('rp.candidate IN (:candidates)')
            ->andWhere('rp.deletedAt IS NULL')
            ->setParameter('organizations', array_map(static fn (ProfileView $v) => $v->organization, $views))
            ->setParameter('candidates', array_map(static fn (ProfileView $v) => $v->candidate, $views))
            ->getQuery()
            ->getScalarResult();

        $pairs = [];
        foreach ($rows as $row) {
            $pairs[$row['organizationId'].':'.$row['candidateId']] = true;
        }

        return $pairs;
    }
}
