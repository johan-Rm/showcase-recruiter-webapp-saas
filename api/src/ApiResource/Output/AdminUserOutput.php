<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\AdminUserProvider;
use DateTimeImmutable;

/**
 * Compte utilisateur pour l'espace ADMIN : tous espaces confondus (candidat / entreprise / admin),
 * avec ses rôles, son statut et sa dernière connexion (tâche 100). Enveloppe plate paginée.
 * Lecture seule, gardée ROLE_ADMIN.
 */
#[ApiResource(
    shortName: 'AdminUser',
    operations: [
        new Get(
            uriTemplate: '/admin/users',
            output: CollectionOutput::class,
            name: 'admin_users',
            provider: AdminUserProvider::class,
            paginationEnabled: false,
            read: true,
        ),
    ],
)]
final class AdminUserOutput
{
    /** @param list<string> $roles */
    public function __construct(
        public string $id,
        public string $givenName,
        public string $familyName,
        public string $email,
        public array $roles,
        public string $status,
        public ?DateTimeImmutable $lastLoginAt,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
