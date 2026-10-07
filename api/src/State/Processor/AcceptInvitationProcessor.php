<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\AcceptInvitationInput;
use App\ApiResource\Output\RecruiterOutput;
use App\Domain\Organization\EmailAlreadyRegisteredException;
use App\Domain\Organization\InvitationAcceptor;
use App\Domain\Organization\InvitationNotAcceptableException;
use App\Repository\InvitationRepository;
use App\State\OrganizationMapper;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * POST /v1/invitations/{token}/accept : parcours public, crée le compte membre depuis le token.
 * Token inconnu → 404 ; invitation expirée/déjà acceptée → 410 ; email déjà inscrit → 409.
 *
 * @implements ProcessorInterface<AcceptInvitationInput, RecruiterOutput>
 */
final readonly class AcceptInvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private InvitationRepository $invitations,
        private InvitationAcceptor $acceptor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RecruiterOutput
    {
        $token = $uriVariables['token'] ?? null;
        $invitation = \is_string($token) ? $this->invitations->findOneByToken($token) : null;
        if (null === $invitation) {
            throw new NotFoundHttpException();
        }

        try {
            $recruiter = $this->acceptor->accept($invitation, $data->password, $data->givenName, $data->familyName);
        } catch (InvitationNotAcceptableException $e) {
            throw new GoneHttpException($e->getMessage(), $e);
        } catch (EmailAlreadyRegisteredException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return OrganizationMapper::toMember($recruiter);
    }
}
