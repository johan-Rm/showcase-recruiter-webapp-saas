<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InvitationStatus;
use App\Enum\OrganizationRole;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Invitation d'un collègue par l'owner d'une entreprise. Cf. data-model.md § Onboarding.
 */
#[ORM\Entity]
#[ORM\Table(name: 'invitation')]
class Invitation extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\Column(length: 180)]
        public string $email,
        #[ORM\Column(length: 64, unique: true)]
        public string $token,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'invited_by_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $invitedBy,
        #[ORM\Column(enumType: OrganizationRole::class)]
        public OrganizationRole $role = OrganizationRole::COMPANY_MEMBER,
        #[ORM\Column(enumType: InvitationStatus::class)]
        public InvitationStatus $status = InvitationStatus::PENDING,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $expiresAt = null,
    ) {
        parent::__construct();
    }
}
