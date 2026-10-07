<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CandidateOutput;
use App\Domain\Candidate\CandidateExposurePolicy;
use App\Domain\Candidate\ProfileViewRecorder;
use App\Domain\Candidate\RevealedAccess;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Entity\User;
use App\Repository\CandidateRepository;
use App\State\CandidateMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/candidates/{id} : profil détaillé pour un recruteur. Coordonnées masquées tant que
 * l'entreprise du recruteur n'a pas dévoilé le candidat.
 *
 * @implements ProviderInterface<CandidateOutput>
 */
final readonly class CandidateItemProvider implements ProviderInterface
{
    public function __construct(
        private CandidateRepository $candidates,
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
        private CandidateExposurePolicy $candidateExposurePolicy,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private ProfileViewRecorder $profileViewRecorder,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CandidateOutput
    {
        $id = $uriVariables['id'] ?? null;
        $candidate = \is_string($id) ? $this->candidates->findOneByUuid($id) : null;
        if (null === $candidate) {
            throw new NotFoundHttpException();
        }

        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;
        if (!$recruiter instanceof Recruiter || $recruiter->organization->isRejected()) {
            throw new AccessDeniedHttpException();
        }

        $granted = [];
        foreach ($this->em->getRepository(RevealedProfile::class)
            ->findBy(['organization' => $recruiter->organization, 'candidate' => $candidate]) as $revealed) {
            $granted[$revealed->type->value] = true;
        }
        $access = RevealedAccess::fromTypes($granted);

        // Un profil sorti du périmètre reste consultable par qui a DÉJÀ payé quelque chose dessus :
        // on ne reprend pas un accès acheté.
        if (!$access->any() && !$this->candidateExposurePolicy->isExposable($candidate)) {
            throw new NotFoundHttpException();
        }

        $request = $this->requestStack->getCurrentRequest();

        // La sortie est construite AVANT la trace : un échec d'écriture ferme l'EntityManager, et
        // le mapper a encore des relations à charger.
        $output = CandidateMapper::toDetail($candidate, $access);
        $this->profileViewRecorder->record($candidate, $recruiter);

        return $output;
    }
}
