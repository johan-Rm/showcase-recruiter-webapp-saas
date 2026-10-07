<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\InvitationOutput;
use App\ApiResource\Output\OrganizationOutput;
use App\ApiResource\Output\RecruiterOutput;
use App\Entity\Invitation;
use App\Entity\Organization;
use App\Entity\Recruiter;

/**
 * Mapping Entity → Output DTO plat pour le cluster entreprise (réutilisé par providers/processor).
 */
final class OrganizationMapper
{
    public static function toOutput(Organization $organization): OrganizationOutput
    {
        return new OrganizationOutput(
            $organization->getUuid()->toString(),
            $organization->name,
            $organization->legalName,
            $organization->taxId,
            $organization->country,
            $organization->city,
            $organization->billingAddress,
            $organization->website,
            $organization->logo,
            $organization->sector,
            $organization->description,
            $organization->acquisitionChannel,
            $organization->acquisitionChannelDetail,
            $organization->validationStatus,
            $organization->jarviCompanyId,
            $organization->jarviCompanySyncStatus->value,
            $organization->jarviCompanySyncedAt,
            $organization->getCreatedAt(),
            $organization->getUpdatedAt(),
        );
    }

    public static function toMember(Recruiter $recruiter): RecruiterOutput
    {
        $person = $recruiter->getPerson();

        return new RecruiterOutput(
            $recruiter->getUuid()->toString(),
            $person->givenName,
            $person->familyName,
            $person->contactEmail,
            $recruiter->organizationRole->value,
            $recruiter->jobTitle,
            $recruiter->status->value,
        );
    }

    public static function toInvitation(Invitation $invitation): InvitationOutput
    {
        return new InvitationOutput(
            $invitation->getUuid()->toString(),
            $invitation->email,
            $invitation->role->value,
            $invitation->status->value,
            $invitation->token,
            $invitation->invitedBy->getUuid()->toString(),
            $invitation->expiresAt,
            $invitation->getCreatedAt(),
        );
    }
}
