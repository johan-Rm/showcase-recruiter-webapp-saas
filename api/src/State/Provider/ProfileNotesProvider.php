<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\ProfileNoteOutput;
use App\Entity\ProfileNote;
use App\Repository\CandidateRepository;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CollaborationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/organizations/{organizationId}/candidates/{candidateId}/notes : notes partagées (membre).
 *
 * @implements ProviderInterface<CollectionOutput<ProfileNoteOutput>>
 */
final readonly class ProfileNotesProvider implements ProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $candidateId = $uriVariables['candidateId'] ?? null;
        $candidate = \is_string($candidateId) ? $this->candidates->findOneByUuid($candidateId) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }

        $notes = $this->em->getRepository(ProfileNote::class)
            ->findBy(['organization' => $organization, 'candidate' => $candidate], ['createdAt' => 'DESC', 'id' => 'DESC']);

        $items = array_map(
            static fn (ProfileNote $note): ProfileNoteOutput => CollaborationMapper::toNote($note),
            $notes,
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }
}
