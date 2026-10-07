<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Token hashé, usage unique, permettant à un utilisateur de réinitialiser son mot de passe.
 */
#[ORM\Entity]
#[ORM\Table(name: 'password_reset_token')]
#[ORM\Index(name: 'idx_password_reset_token_user', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_password_reset_token_hash', columns: ['token_hash'])]
class PasswordResetToken extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
        public User $user,
        #[ORM\Column(length: 64)]
        public string $tokenHash,
        #[ORM\Column]
        public DateTimeImmutable $expiresAt,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $consumedAt = null,
    ) {
        parent::__construct();
    }

    public function isConsumable(DateTimeImmutable $now): bool
    {
        return null === $this->consumedAt && $this->expiresAt > $now;
    }
}
