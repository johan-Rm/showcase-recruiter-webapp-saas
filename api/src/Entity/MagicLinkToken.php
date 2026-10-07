<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Token Magic Link hashé, usage unique, pour authentifier un déclarant entreprise.
 */
#[ORM\Entity]
#[ORM\Table(name: 'magic_link_token')]
#[ORM\Index(name: 'idx_magic_link_token_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_magic_link_token_hash', columns: ['token_hash'])]
class MagicLinkToken extends AbstractEntity
{
    public function __construct(
        #[ORM\Column(length: 180)]
        public string $email,
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
