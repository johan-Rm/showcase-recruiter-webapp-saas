<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\AcceptInvitationInput;
use App\ApiResource\Input\InvitationInput;
use App\State\Processor\AcceptInvitationProcessor;
use App\State\Processor\InvitationProcessor;
use App\State\Provider\InvitationAcceptanceProvider;
use App\State\Provider\InvitationsProvider;
use DateTimeImmutable;

/**
 * Invitation d'un collègue (réponse plate). Émission/lecture réservées à l'admin entreprise (voter MANAGE) ;
 * acceptation publique par token (crée le compte membre). En V1 le `token` est retourné car
 * l'email réel n'est pas encore envoyé. `id` non-identifiant : pas d'IRI à générer.
 */
#[ApiResource(
    shortName: 'Invitation',
    operations: [
        new Post(
            uriTemplate: '/organizations/{organizationId}/invitations',
            uriVariables: ['organizationId'],
            input: InvitationInput::class,
            processor: InvitationProcessor::class,
        ),
        new Get(
            uriTemplate: '/organizations/{organizationId}/invitations',
            uriVariables: ['organizationId'],
            output: CollectionOutput::class,
            provider: InvitationsProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/invitations/{token}',
            uriVariables: ['token'],
            output: InvitationAcceptanceOutput::class,
            provider: InvitationAcceptanceProvider::class,
            paginationEnabled: false,
        ),
        new Post(
            uriTemplate: '/invitations/{token}/accept',
            uriVariables: ['token'],
            input: AcceptInvitationInput::class,
            output: RecruiterOutput::class,
            read: false,
            processor: AcceptInvitationProcessor::class,
        ),
    ],
)]
final class InvitationOutput
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $email,
        public string $role,
        public string $status,
        public string $token,
        public string $invitedById,
        public ?DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
