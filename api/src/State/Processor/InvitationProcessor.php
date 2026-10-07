<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\InvitationInput;
use App\ApiResource\Output\InvitationOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Organization\EmailAlreadyInvitedException;
use App\Domain\Organization\EmailAlreadyMemberException;
use App\Domain\Organization\InvitationEmailSender;
use App\Domain\Organization\MemberInviter;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\OrganizationRole;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\OrganizationMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * POST /v1/organizations/{organizationId}/invitations : l'admin entreprise invite un collègue (voter MANAGE).
 * Anti-doublon (email déjà membre / invitation PENDING) → 409.
 *
 * @implements ProcessorInterface<InvitationInput, InvitationOutput>
 */
final readonly class InvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private MemberInviter $inviter,
        private InvitationEmailSender $invitationEmailSender,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InvitationOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->security->getUser();
        $invitedBy = $user instanceof User
            ? $this->currentRecruiterResolver->resolveForOrganization($user, $organization)
            : null;
        if (!$invitedBy instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        try {
            $invitation = $this->inviter->invite(
                $organization,
                $invitedBy,
                $data->email,
                OrganizationRole::from($data->role),
            );
        } catch (EmailAlreadyMemberException|EmailAlreadyInvitedException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        $this->invitationEmailSender->send($invitation);

        return OrganizationMapper::toInvitation($invitation);
    }
}
