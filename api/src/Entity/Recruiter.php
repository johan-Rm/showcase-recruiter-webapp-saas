<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OrganizationRole;
use App\Enum\RecruiterStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * Membre recruteur d'une entreprise (1-1 Person). En V1, un Recruiter appartient à une Organization.
 * ROLE_COMPANY_OWNER : crée l'Organization, achète les crédits, invite/gère les droits. Cf. data-model.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'recruiter')]
class Recruiter extends AbstractEntity
{
    public function __construct(
        #[ORM\OneToOne(targetEntity: Person::class, cascade: ['persist'])]
        #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: false, unique: true)]
        private Person $person,
        #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'recruiters')]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\Column(enumType: OrganizationRole::class)]
        public OrganizationRole $organizationRole = OrganizationRole::COMPANY_MEMBER,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $jobTitle = null,
        #[ORM\Column(enumType: RecruiterStatus::class)]
        public RecruiterStatus $status = RecruiterStatus::ACTIVE,
    ) {
        parent::__construct();
    }

    public function getPerson(): Person
    {
        return $this->person;
    }
}
