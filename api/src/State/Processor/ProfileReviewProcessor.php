<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\ProfileReviewInput;
use App\ApiResource\Output\ProfileReviewOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SearchActivityRecorder;
use App\Entity\Candidate;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchProfileReview;
use App\Entity\User;
use App\Enum\ProfileReviewAction;
use App\Repository\CandidateRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchProfileReviewRepository;
use App\Repository\SearchResultMembershipRepository;
use App\Security\SavedSearchVoter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /v1/searches/{searchId}/reviews — déclarer en lot des profils vus, écartés ou remis.
 *
 * L'état est PERSONNEL : il s'écrit sur le parcours du recruteur courant, pas sur la recherche. D'où
 * une autorisation en **lecture** et non en écriture — le destinataire d'une recherche partagée, qui
 * ne peut ni la modifier ni la favoriser, doit pouvoir la trier. Lui refuser ce droit rendrait le
 * partage inutilisable : il repasserait sur le jeu entier sans jamais pouvoir noter son avancement.
 *
 * @implements ProcessorInterface<ProfileReviewInput, ProfileReviewOutput>
 */
final readonly class ProfileReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private SavedSearchRepository $searches,
        private CandidateRepository $candidates,
        private SearchProfileReviewRepository $reviews,
        private SearchResultMembershipRepository $memberships,
        private EntityManagerInterface $em,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchActivityRecorder $activity,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProfileReviewOutput
    {
        $searchId = $uriVariables['searchId'] ?? null;
        $search = \is_string($searchId) ? $this->searches->findOneByUuid($searchId) : null;
        if (!$search instanceof SavedSearch || !$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }
        $recruiter = $this->currentRecruiter();
        $action = ProfileReviewAction::from($data->action);
        $now = new DateTimeImmutable();

        $candidates = $this->membersOf($search, $data->candidateIds);
        $existing = $this->reviews->existingFor($search, $recruiter, array_map(
            static fn (Candidate $candidate): int => (int) $candidate->getId(),
            $candidates,
        ));

        foreach ($candidates as $candidate) {
            $review = $existing[(int) $candidate->getId()]
                ?? new SearchProfileReview($search, $candidate, $recruiter);

            match ($action) {
                // Écarter implique d'avoir regardé : on ne rejette pas ce qu'on n'a pas vu, et
                // laisser le profil « non vu » le ferait ressortir au prochain « reprendre ».
                ProfileReviewAction::SEEN => $review->seenAt ??= $now,
                ProfileReviewAction::DISMISS => [$review->seenAt ??= $now, $review->dismissedAt = $now],
                // Remettre efface l'écart, jamais le fait d'avoir vu.
                ProfileReviewAction::RESTORE => $review->dismissedAt = null,
            };

            $this->em->persist($review);
        }

        $this->em->flush();
        // Trancher est le geste de travail par excellence : il repousse la clôture automatique.
        $this->activity->record($search);

        $states = $this->reviews->statesFor($search, $recruiter);

        return new ProfileReviewOutput(
            \count($states['seen']),
            \count($states['dismissed']),
            $this->memberships->count(['search' => $search]),
        );
    }

    /**
     * Les profils désignés, à condition qu'ils appartiennent au jeu de CETTE recherche.
     *
     * Sans ce contrôle, poster des identifiants arbitraires permettrait de se constituer un
     * avancement sur des profils qu'on n'a jamais eus sous les yeux. Le refus les NOMME plutôt que
     * de rétrécir le lot en silence.
     *
     * @param list<string> $candidateIds
     *
     * @return list<Candidate>
     */
    private function membersOf(SavedSearch $search, array $candidateIds): array
    {
        $members = $this->memberships->candidateIdsFor($search);

        $candidates = [];
        $outside = [];
        foreach (array_unique($candidateIds) as $uuid) {
            $candidate = $this->candidates->findOneByUuid($uuid);

            if (!$candidate instanceof Candidate || !isset($members[(int) $candidate->getId()])) {
                $outside[] = $uuid;
                continue;
            }

            $candidates[] = $candidate;
        }

        if ([] !== $outside) {
            throw new UnprocessableEntityHttpException(\sprintf('Profils absents du jeu de cette recherche : %s.', implode(', ', $outside)));
        }

        return $candidates;
    }

    private function currentRecruiter(): Recruiter
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;
        if (!$recruiter instanceof Recruiter) {
            throw new AccessDeniedHttpException();
        }

        return $recruiter;
    }
}
