<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Compte d'authentification, relié 1-1 à un Person. Casquettes multi-valuées via roles[]
 * (ROLE_CANDIDATE / ROLE_COMPANY_OWNER / ROLE_COMPANY_MEMBER / ROLE_ADMIN_OWNER / ROLE_ADMIN_MEMBER)
 * + existence des lignes Candidate/Recruiter.
 * Cf. data-model.md § Socle identité & comptes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private string $password = '';

    /** Dernière connexion réussie (tâche 100) — posée par LastLoginSubscriber sur le firewall `login`. */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    public function __construct(
        #[ORM\Column(length: 180, unique: true)]
        private string $email,
        #[ORM\OneToOne(targetEntity: Person::class, cascade: ['persist'])]
        #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: false, unique: true)]
        private Person $person,
        #[ORM\Column(enumType: UserStatus::class)]
        public UserStatus $status = UserStatus::ACTIVE,
    ) {
        parent::__construct();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): void
    {
        $this->lastLoginAt = $lastLoginAt;
    }

    public function eraseCredentials(): void
    {
    }
}
