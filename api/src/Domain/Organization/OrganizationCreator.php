<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Credit\CreditPolicy;
use App\Entity\CreditTransaction;
use App\Entity\CreditWallet;
use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\CreditTransactionType;
use App\Enum\OrganizationRole;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Création d'une entreprise (cf. data-model.md) : le Person courant devient ROLE_COMPANY_OWNER,
 * son wallet est crédité des crédits offerts, et la transaction GRANT_FREE est tracée — le tout de façon
 * atomique. Règle V1 : un Person déjà recruteur ne peut pas créer une seconde entreprise.
 */
final readonly class OrganizationCreator
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function create(User $user, OrganizationDraft $draft): Organization
    {
        $person = $user->getPerson();

        if (null !== $this->em->getRepository(Recruiter::class)->findOneBy(['person' => $person])) {
            throw new OrganizationAlreadyOwnedException();
        }

        return $this->em->wrapInTransaction(function () use ($user, $person, $draft): Organization {
            // Le Magic Link ne capte que l'e-mail (nom/prénom dérivés) : l'inscription entreprise fixe
            // l'identité réelle du déclarant, propagée ensuite vers le contact Jarvi.
            $person->givenName = $draft->givenName;
            $person->familyName = $draft->familyName;

            $organization = new Organization(
                $draft->name,
                legalName: $draft->legalName,
                taxId: $draft->taxId,
                country: $draft->country ?? 'MA',
                city: $draft->city,
                billingAddress: $draft->billingAddress,
                website: $draft->website,
                sector: $draft->sector,
                description: $draft->description,
                acquisitionChannel: $draft->acquisitionChannel,
                acquisitionChannelDetail: $draft->acquisitionChannelDetail,
                acquisitionReferrer: $draft->acquisitionReferrer,
            );
            $this->em->persist($organization);

            $this->em->persist(new CreditWallet($organization, CreditPolicy::WELCOME_CREDITS));
            $this->em->persist(new CreditTransaction(
                $organization,
                CreditTransactionType::GRANT_FREE,
                CreditPolicy::WELCOME_CREDITS,
                CreditPolicy::WELCOME_CREDITS,
                reason: 'Crédits offerts à la création',
            ));

            $this->em->persist(new Recruiter($person, $organization, OrganizationRole::COMPANY_OWNER, $draft->jobTitle));

            $roles = array_values(array_filter(
                $user->getRoles(),
                static fn (string $role): bool => 'ROLE_USER' !== $role,
            ));
            $roles[] = OrganizationRole::COMPANY_OWNER->value;
            $user->setRoles(array_values(array_unique($roles)));

            return $organization;
        });
    }
}
