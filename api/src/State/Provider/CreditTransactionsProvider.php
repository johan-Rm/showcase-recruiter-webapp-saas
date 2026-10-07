<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\CreditTransactionOutput;
use App\Entity\CreditTransaction;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CreditMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/organizations/{organizationId}/credit-transactions : journal (membre), du plus récent au plus ancien.
 *
 * @implements ProviderInterface<CollectionOutput<CreditTransactionOutput>>
 */
final readonly class CreditTransactionsProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $transactions = $this->em->getRepository(CreditTransaction::class)
            ->findBy(['organization' => $organization], ['createdAt' => 'DESC', 'id' => 'DESC']);

        $items = array_map(
            static fn (CreditTransaction $transaction): CreditTransactionOutput => CreditMapper::toTransaction($transaction),
            $transactions,
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
