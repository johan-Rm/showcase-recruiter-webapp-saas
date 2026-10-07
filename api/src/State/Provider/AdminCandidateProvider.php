<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminCandidateOutput;
use App\ApiResource\Output\CollectionOutput;
use App\Entity\Candidate;
use App\Repository\CandidateRepository;
use App\State\CandidateMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/candidates : le vivier cross-tenant NON masqué, réservé aux rôles admin. La garde
 * ROLE_ADMIN est explicite ici (accès cross-tenant), pas dans security.yaml — même convention que
 * OrganizationReviewProvider. Enveloppe plate paginée, pagination faite main (API Platform désactivée).
 *
 * @implements ProviderInterface<CollectionOutput<AdminCandidateOutput>>
 */
final readonly class AdminCandidateProvider implements ProviderInterface
{
    private const int PER_PAGE_DEFAULT = 20;
    private const int PER_PAGE_MAX = 100;

    public function __construct(
        private CandidateRepository $candidates,
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

        $items = array_map(
            static fn (Candidate $candidate): AdminCandidateOutput => CandidateMapper::toAdmin($candidate),
            $this->candidates->findForAdmin($page, $perPage),
        );

        return new CollectionOutput($items, $this->candidates->countForAdmin(), $page, $perPage);
    }
}
