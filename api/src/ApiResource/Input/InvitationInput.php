<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\OrganizationRole;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'invitation d'un collègue par un ROLE_COMPANY_OWNER. En V1 seul le rôle
 * ROLE_COMPANY_MEMBER est invitable. Cf. api-structure.md.
 */
final class InvitationInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\Choice(callback: [self::class, 'invitableRoles'], message: 'Rôle d\'invitation non autorisé.')]
    public string $role = OrganizationRole::COMPANY_MEMBER->value;

    /**
     * @return list<string>
     */
    public static function invitableRoles(): array
    {
        return [OrganizationRole::COMPANY_MEMBER->value];
    }
}
