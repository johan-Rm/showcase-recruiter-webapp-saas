<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Output\OrganizationOutput;
use App\Domain\Organization\OrganizationJarviCompanySynchronizer;
use App\Domain\Organization\OrganizationValidationNotifier;
use App\Enum\AdminRole;
use App\Enum\OrganizationValidationStatus;
use App\Repository\OrganizationRepository;
use App\State\OrganizationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validation/refus admin d'une Organization. La sync Jarvi company ne part que sur validation,
 * et reste désactivée par défaut via feature flag.
 *
 * Ni l'une ni l'autre décision n'ouvre ou ne referme un accès par elle-même : l'accès s'ouvre à
 * l'inscription, et c'est le statut posé ici que lisent ensuite les gardes.
 *
 * @implements ProcessorInterface<mixed, OrganizationOutput>
 */
final readonly class OrganizationValidationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
        private OrganizationJarviCompanySynchronizer $jarviSynchronizer,
        private OrganizationValidationNotifier $validationNotifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrganizationOutput
    {
        $id = $uriVariables['id'] ?? null;
        $organization = \is_string($id) ? $this->organizations->findOneByUuid($id) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->isAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $wasValidated = $organization->isValidated();

        if ('organization_reject' === $operation->getName()) {
            // Suspension : rien à propager sur les comptes, les gardes et le checker lisent ce statut.
            $organization->validationStatus = OrganizationValidationStatus::REFUSE;
        } else {
            $organization->validationStatus = OrganizationValidationStatus::VALIDEE;
            $this->jarviSynchronizer->sync($organization);
            // Une entreprise déjà validée ne redéclenche rien : sans ce test, une seconde validation
            // annoncerait une nouvelle qui date.
            if (!$wasValidated) {
                $this->validationNotifier->notify($organization);
            }
        }

        $this->em->flush();

        return OrganizationMapper::toOutput($organization);
    }

    private function isAdmin(): bool
    {
        $user = $this->security->getUser();

        return null !== $user && [] !== array_intersect(
            [AdminRole::ADMIN_MEMBER->value, AdminRole::ADMIN_OWNER->value],
            $user->getRoles(),
        );
    }
}
