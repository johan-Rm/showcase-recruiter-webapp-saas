<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminOrganizationDetailOutput;
use App\ApiResource\Output\CreditTransactionOutput;
use App\ApiResource\Output\RecruiterOutput;
use App\Entity\CreditTransaction;
use App\Entity\CreditWallet;
use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\Subscription;
use App\Repository\OrganizationRepository;
use App\State\CreditMapper;
use App\State\OrganizationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/admin/organizations/{id} : solde, abonnement, membres et journal de crédits d'une
 * entreprise. Garde ROLE_ADMIN explicite — la route membre équivalente, elle, exige d'appartenir à
 * l'organisation, ce qui empêchait justement l'administration de répondre à un client.
 *
 * @implements ProviderInterface<AdminOrganizationDetailOutput>
 */
final readonly class AdminOrganizationDetailProvider implements ProviderInterface
{
    private const int TRANSACTIONS_DEFAULT = 20;
    private const int TRANSACTIONS_MAX = 100;

    public function __construct(
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminOrganizationDetailOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $id = $uriVariables['id'] ?? null;
        $organization = \is_string($id) ? $this->organizations->findOneByUuid($id) : null;
        if (!$organization instanceof Organization) {
            throw new NotFoundHttpException();
        }

        $wallet = $this->em->getRepository(CreditWallet::class)->findOneBy(['organization' => $organization]);
        $subscription = $this->em->getRepository(Subscription::class)->findOneBy(['organization' => $organization]);

        return new AdminOrganizationDetailOutput(
            $organization->getUuid()->toString(),
            $organization->name,
            $organization->legalName,
            $organization->country,
            $organization->city,
            $organization->validationStatus,
            $organization->getCreatedAt(),
            $wallet instanceof CreditWallet ? $wallet->balance : 0,
            $this->subscription($subscription),
            array_map(
                static fn (Recruiter $recruiter): RecruiterOutput => OrganizationMapper::toMember($recruiter),
                $organization->getRecruiters()->toArray(),
            ),
            $this->transactions($organization),
        );
    }

    /** @return array{plan: string, status: string, currentPeriodEnd: ?string}|null */
    private function subscription(?Subscription $subscription): ?array
    {
        if (!$subscription instanceof Subscription) {
            return null;
        }

        return [
            'plan' => $subscription->plan,
            'status' => $subscription->status->value,
            'currentPeriodEnd' => $subscription->currentPeriodEnd?->format('c'),
        ];
    }

    /** @return list<CreditTransactionOutput> */
    private function transactions(Organization $organization): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $limit = min(
            self::TRANSACTIONS_MAX,
            max(1, $request?->query->getInt('transactions', self::TRANSACTIONS_DEFAULT) ?? self::TRANSACTIONS_DEFAULT),
        );

        return array_map(
            static fn (CreditTransaction $transaction): CreditTransactionOutput => CreditMapper::toTransaction($transaction),
            $this->em->getRepository(CreditTransaction::class)->findBy(
                ['organization' => $organization],
                ['createdAt' => 'DESC', 'id' => 'DESC'],
                $limit,
            ),
        );
    }
}
