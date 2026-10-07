<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Enum\OrganizationValidationStatus;
use App\State\Provider\AdminOrganizationDetailProvider;
use DateTimeImmutable;

/**
 * GET /v1/admin/organizations/{id} — LA FICHE D'UNE ENTREPRISE POUR L'ADMINISTRATION.
 *
 * Répond à la question la plus courante d'un appel client : combien de crédits lui reste-t-il, et
 * sur quel abonnement est-il. Le solde seul ne se vérifie pas — le journal des mouvements l'explique,
 * chaque ligne portant le solde obtenu après l'opération.
 *
 * Lecture seule et gardée `ROLE_ADMIN`. Créditer une entreprise à la main est un geste commercial,
 * qui n'entre pas ici.
 */
#[ApiResource(
    shortName: 'AdminOrganizationDetail',
    operations: [
        new Get(
            uriTemplate: '/admin/organizations/{id}',
            uriVariables: ['id'],
            name: 'admin_organization_detail',
            provider: AdminOrganizationDetailProvider::class,
            read: true,
        ),
    ],
)]
final class AdminOrganizationDetailOutput
{
    /**
     * @param list<RecruiterOutput>         $members
     * @param list<CreditTransactionOutput> $creditTransactions
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $legalName,
        public ?string $country,
        public ?string $city,
        public OrganizationValidationStatus $validationStatus,
        public DateTimeImmutable $createdAt,
        /** Zéro quand l'entreprise n'a jamais eu de portefeuille — jamais une absence de champ. */
        public int $creditBalance,
        /**
         * L'abonnement, ou `null` quand l'entreprise n'en a pas.
         *
         * Sous-objet plutôt que trois champs plats : `null` dit « aucun abonnement » sans laisser
         * l'écran deviner ce que valent trois champs vides côte à côte.
         *
         * @var array{plan: string, status: string, currentPeriodEnd: ?string}|null
         */
        public ?array $subscription,
        public array $members,
        /** Du plus récent au plus ancien, borné : une entreprise active en accumule beaucoup. */
        public array $creditTransactions,
    ) {
    }
}
