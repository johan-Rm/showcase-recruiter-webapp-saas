<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\InvitationAcceptanceOutput;
use App\Enum\InvitationStatus;
use App\Repository\InvitationRepository;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/invitations/{token} : contexte public minimal avant acceptation.
 *
 * @implements ProviderInterface<InvitationAcceptanceOutput>
 */
final readonly class InvitationAcceptanceProvider implements ProviderInterface
{
    public function __construct(private InvitationRepository $invitations)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): InvitationAcceptanceOutput
    {
        $token = $uriVariables['token'] ?? null;
        $invitation = \is_string($token) ? $this->invitations->findOneByToken($token) : null;
        if (null === $invitation) {
            throw new NotFoundHttpException();
        }

        if (
            InvitationStatus::PENDING !== $invitation->status
            || null === $invitation->expiresAt
            || $invitation->expiresAt < new DateTimeImmutable()
        ) {
            throw new GoneHttpException('Invitation expirée ou déjà utilisée.');
        }

        return new InvitationAcceptanceOutput(
            $invitation->email,
            $invitation->organization->name,
            $invitation->role->value,
            $invitation->expiresAt,
        );
    }
}
