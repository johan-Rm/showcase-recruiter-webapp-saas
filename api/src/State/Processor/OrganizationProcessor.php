<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\OrganizationInput;
use App\ApiResource\Output\OrganizationOutput;
use App\Domain\Auth\PasswordSetupIssuer;
use App\Domain\Organization\OrganizationAlreadyOwnedException;
use App\Domain\Organization\OrganizationCreator;
use App\Domain\Organization\OrganizationDraft;
use App\Entity\User;
use App\Enum\AcquisitionChannel;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\OrganizationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * POST /v1/organizations   → création (le user courant devient ROLE_COMPANY_OWNER) via Domain\OrganizationCreator.
 * PUT  /v1/organizations/{uuid} → mise à jour (ROLE_COMPANY_OWNER uniquement, voter).
 *
 * @implements ProcessorInterface<OrganizationInput, OrganizationOutput>
 */
final readonly class OrganizationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationCreator $creator,
        private OrganizationRepository $organizations,
        private EntityManagerInterface $em,
        private PasswordSetupIssuer $passwordSetupIssuer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrganizationOutput
    {
        $id = $uriVariables['id'] ?? null;

        return \is_string($id)
            ? $this->update($id, $data)
            : $this->create($data);
    }

    private function create(OrganizationInput $input): OrganizationOutput
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        try {
            $organization = $this->creator->create($user, new OrganizationDraft(
                name: $input->name,
                givenName: $input->givenName,
                familyName: $input->familyName,
                legalName: $input->legalName,
                taxId: $input->taxId,
                country: $input->country,
                city: $input->city,
                billingAddress: $input->billingAddress,
                website: $input->website,
                sector: $input->sector,
                description: $input->description,
                jobTitle: $input->jobTitle,
                acquisitionChannel: AcquisitionChannel::tryFrom($input->acquisitionChannel ?? ''),
                acquisitionChannelDetail: $input->acquisitionChannelDetail,
                acquisitionReferrer: $input->acquisitionReferrer,
            ));
        } catch (OrganizationAlreadyOwnedException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        // L'accès ne dépend plus de la revue : le déclarant a rempli son formulaire, il lui manque
        // seulement un mot de passe. Le lien part donc maintenant, pendant qu'il est encore devant
        // son écran — et non au bout d'une attente qu'il n'aurait pas choisie.
        $this->passwordSetupIssuer->issue($user);

        return OrganizationMapper::toOutput($organization);
    }

    private function update(string $uuid, OrganizationInput $input): OrganizationOutput
    {
        $organization = $this->organizations->findOneByUuid($uuid);
        if (null === $organization) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $organization)) {
            throw new AccessDeniedHttpException();
        }

        // Push Jarvi = unique, à la validation (cf. OrganizationValidationProcessor). Aucune synchro
        // ensuite : une entreprise validée modifie librement son identité, sans re-validation ni re-push.
        $organization->name = $input->name;
        $organization->legalName = $input->legalName;
        $organization->taxId = $input->taxId;
        $organization->country = $input->country;
        $organization->city = $input->city;
        $organization->billingAddress = $input->billingAddress;
        $organization->website = $input->website;
        $organization->sector = $input->sector;
        $organization->description = $input->description;
        // La provenance n'est volontairement pas réécrite : figée à l'inscription (cf. inscription.md).

        $this->em->flush();

        return OrganizationMapper::toOutput($organization);
    }
}
