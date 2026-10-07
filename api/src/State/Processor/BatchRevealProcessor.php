<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\BatchRevealInput;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\RevealedProfileOutput;
use App\Domain\Candidate\CandidateExposurePolicy;
use App\Domain\Credit\InsufficientCreditsException;
use App\Domain\Credit\ProfileRevealer;
use App\Domain\Credit\RevealBatchTooLargeException;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Candidate;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Entity\User;
use App\Enum\RevealType;
use App\Repository\CandidateRepository;
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use App\State\CreditMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /v1/organizations/{organizationId}/revealed-profiles/batch — dévoiler un LOT (task 163).
 *
 * Un seul accès pour tous les profils désignés, TOUT OU RIEN, et plafonné : c'est l'action la plus
 * coûteuse du produit, un clic sur une recherche large peut viser des centaines de profils.
 *
 * Les invariants (atomicité, idempotence, solde ≥ 0, plafond) vivent dans le Domain — ici on résout,
 * on autorise, et on traduit les refus en codes HTTP.
 *
 * @implements ProcessorInterface<BatchRevealInput, CollectionOutput<RevealedProfileOutput>>
 */
final readonly class BatchRevealProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private OrganizationRepository $organizations,
        private CandidateRepository $candidates,
        private ProfileRevealer $revealer,
        private CandidateExposurePolicy $candidateExposurePolicy,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $organizationId = $uriVariables['organizationId'] ?? null;
        $organization = \is_string($organizationId) ? $this->organizations->findOneByUuid($organizationId) : null;
        if (null === $organization) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(OrganizationVoter::ACCESS, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveForOrganization($user, $organization)
            : null;
        if (!$recruiter instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        try {
            $revealed = $this->revealer->revealMany(
                $organization,
                $this->resolve($data->candidateIds),
                $recruiter,
                RevealType::from($data->type),
            );
        } catch (RevealBatchTooLargeException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        } catch (InsufficientCreditsException $e) {
            // Le lot entier est refusé : mieux vaut ne rien dévoiler que d'en dévoiler la moitié.
            throw new HttpException(Response::HTTP_PAYMENT_REQUIRED, $e->getMessage(), $e);
        }

        $items = array_map(
            static fn (RevealedProfile $profile): RevealedProfileOutput => CreditMapper::toRevealed($profile),
            $revealed,
        );

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }

    /**
     * Les profils désignés, ou un refus qui les NOMME.
     *
     * Un identifiant inconnu — ou un candidat sorti du périmètre — fait échouer le lot entier plutôt
     * que de le rétrécir en silence : on ne débite pas 19 profils quand l'utilisateur en a coché 20.
     *
     * @param list<string> $candidateIds
     *
     * @return list<Candidate>
     */
    private function resolve(array $candidateIds): array
    {
        $candidates = [];
        $unknown = [];

        foreach (array_unique($candidateIds) as $uuid) {
            $candidate = $this->candidates->findOneByUuid($uuid);

            if (!$candidate instanceof Candidate || !$this->candidateExposurePolicy->isExposable($candidate)) {
                $unknown[] = $uuid;
                continue;
            }

            $candidates[] = $candidate;
        }

        if ([] !== $unknown) {
            throw new UnprocessableEntityHttpException(\sprintf('Profils introuvables ou hors périmètre : %s.', implode(', ', $unknown)));
        }

        return $candidates;
    }
}
