<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Socle commun à toutes les entités showcase-recruiter-webapp-saas (cf. data-model.md § Conventions communes) :
 * PK interne `id` bigint (jointures, jamais exposée) + `uuid` public (UUIDv7, exposé en API)
 * + timestamps + soft delete.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    protected ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    protected Uuid $uuid;

    #[ORM\Column]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column]
    protected DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    /**
     * Réciproque de {@see softDelete()} : une ligne retirée puis rétablie reprend sa place plutôt
     * que d'être recréée. Sans elle, une contrainte d'unicité rejetterait le doublon — le cas se
     * pose dès qu'une source versionnée fait autorité et peut revenir sur un retrait.
     */
    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }
}
