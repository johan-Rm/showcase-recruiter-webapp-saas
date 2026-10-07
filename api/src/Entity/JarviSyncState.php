<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Curseur de synchro de l'ingestion Jarvi (tâche 020). Une ligne par flux (ex. `profiles`) :
 * `cursor` = borne `updatedAt` du dernier passage incrémental ; `lastRunAt` = dernier déclenchement.
 * Permet de ne retirer de Jarvi que les profils modifiés depuis le dernier run. Cf. jarvi-integration.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'jarvi_sync_state')]
#[ORM\UniqueConstraint(name: 'uniq_jarvi_sync_state_name', columns: ['name'])]
class JarviSyncState extends AbstractEntity
{
    public function __construct(
        #[ORM\Column(length: 64, unique: true)]
        public string $name,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $cursor = null,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $lastRunAt = null,
    ) {
        parent::__construct();
    }
}
