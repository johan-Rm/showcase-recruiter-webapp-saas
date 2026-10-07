<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\OrganizationOutput;
use App\Entity\Organization;
use App\Enum\OrganizationValidationStatus;
use App\Repository\OrganizationRepository;
use App\State\OrganizationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * GET /v1/admin/organizations : file de revue admin (lecture seule, cross-tenant), filtrable par
 * statut de validation. Sans filtre, la route rend toutes les entreprises : c'est l'écran admin qui
 * choisit sa file de travail, pas la route qui la présume.
 *
 * @implements ProviderInterface<CollectionOutput<OrganizationOutput>>
 */
final readonly class OrganizationReviewProvider implements ProviderInterface
{
    private const int PER_PAGE_DEFAULT = 20;
    private const int PER_PAGE_MAX = 100;

    public function __construct(
        private OrganizationRepository $organizations,
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
        $status = $this->status($request?->query->get('status'));
        $page = max(1, $request?->query->getInt('page', 1) ?? 1);
        $perPage = min(self::PER_PAGE_MAX, max(1, $request?->query->getInt('perPage', self::PER_PAGE_DEFAULT) ?? self::PER_PAGE_DEFAULT));

        $items = array_map(
            static fn (Organization $organization): OrganizationOutput => OrganizationMapper::toOutput($organization),
            $this->organizations->findForReview($status, $page, $perPage),
        );

        return new CollectionOutput($items, $this->organizations->countForReview($status), $page, $perPage);
    }

    private function status(mixed $raw): ?OrganizationValidationStatus
    {
        if (!\is_string($raw) || '' === $raw) {
            return null;
        }

        return OrganizationValidationStatus::tryFrom($raw)
            ?? throw new BadRequestHttpException(\sprintf('Statut de validation inconnu : "%s".', $raw));
    }
}
