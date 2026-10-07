<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CreditWalletOutput;
use App\Entity\CreditWallet;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CreditMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/organizations/{organizationId}/credit-wallet : solde courant (membre).
 *
 * @implements ProviderInterface<CreditWalletOutput>
 */
final readonly class CreditWalletProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CreditWalletOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $wallet = $this->em->getRepository(CreditWallet::class)->findOneBy(['organization' => $organization]);
        if (!$wallet instanceof CreditWallet) {
            throw new NotFoundHttpException();
        }

        return CreditMapper::toWallet($wallet);
    }
}
