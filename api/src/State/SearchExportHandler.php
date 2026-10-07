<?php

declare(strict_types=1);

namespace App\State;

use App\Domain\Candidate\RevealedAccess;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SearchActivityRecorder;
use App\Domain\Search\SearchCsvWriter;
use App\Entity\Candidate;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchExport;
use App\Entity\User;
use App\Repository\CandidateRepository;
use App\Repository\RevealedProfileRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\SearchResultMembershipRepository;
use App\Security\SavedSearchVoter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Export CSV d'une SÉLECTION de profils d'une recherche (task 154, ex-task 077) : autorise, vérifie,
 * journalise, rend le fichier. AUCUN crédit n'est débité — la donnée a déjà été payée au dévoilement.
 *
 * On exporte ce qu'on a coché dans la Table, une ligne ou un lot : le produit n'a plus de shortlist,
 * la seule liste de rétention est celle des favoris et elle se pose AVANT de payer.
 *
 * Réservé à l'AUTEUR de la recherche : un destinataire d'une recherche partagée la duplique s'il
 * veut exporter la sienne.
 */
final readonly class SearchExportHandler
{
    public function __construct(
        private Security $security,
        private SavedSearchRepository $searches,
        private CandidateRepository $candidates,
        private SearchResultMembershipRepository $memberships,
        private RevealedProfileRepository $revealed,
        private EntityManagerInterface $em,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchCsvWriter $csv,
        private LoggerInterface $logger,
        private SearchActivityRecorder $activity,
    ) {
    }

    /** @param list<string> $candidateIds */
    public function export(string $searchId, array $candidateIds): Response
    {
        $search = $this->searches->findOneByUuid($searchId);
        if (!$search instanceof SavedSearch) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(SavedSearchVoter::READ, $search)) {
            throw new NotFoundHttpException();
        }
        if (!$this->security->isGranted(SavedSearchVoter::WRITE, $search)) {
            throw new AccessDeniedHttpException("L'export est réservé à l'auteur de la recherche.");
        }

        $recruiter = $this->currentRecruiter();

        // Ce que l'organisation a payé, en UNE requête pour tout le lot plutôt qu'une par candidat.
        // Lu ici et pas deux fois : le filtre s'en sert pour refuser, le writer pour masquer.
        $paid = $this->access($search);
        $candidates = $this->selected($search, $candidateIds, $paid);

        $this->journal($search, $recruiter, $candidates);
        // Exporter est l'aboutissement du travail sur une recherche : elle est tout sauf inactive.
        $this->activity->record($search);

        return $this->response($this->csv->write($candidates, $paid), $this->csv->filename($search->name));
    }

    /** @return array<int, RevealedAccess> */
    private function access(SavedSearch $search): array
    {
        $paid = [];
        foreach ($this->revealed->revealedTypesByCandidate($search->organization) as $candidateId => $types) {
            $paid[$candidateId] = RevealedAccess::fromTypes($types);
        }

        return $paid;
    }

    /**
     * La sélection, validée deux fois.
     *
     * Elle doit appartenir au JEU DE RÉSULTATS de la recherche : sans ce contrôle, l'export
     * deviendrait une porte de sortie universelle — il suffirait de poster n'importe quel
     * identifiant de candidat sur une recherche qu'on possède.
     *
     * Et AU MOINS UNE COORDONNÉE doit être dévoilée — c'est l'accès qui donne sa valeur à une ligne
     * exportée, et une ligne sans aucun moyen de joindre n'a rien à faire dans un fichier qu'on
     * emporte. On refuse en NOMMANT les profils fautifs plutôt que de filtrer en silence : un
     * fichier amputé ne se remarque pas.
     *
     * @param list<string>               $candidateIds
     * @param array<int, RevealedAccess> $paid
     *
     * @return list<Candidate>
     */
    private function selected(SavedSearch $search, array $candidateIds, array $paid): array
    {
        $inSearch = $this->memberships->candidateIdsFor($search);

        $candidates = [];
        $unknown = [];
        $masked = [];

        foreach (array_unique($candidateIds) as $uuid) {
            $candidate = $this->candidates->findOneByUuid($uuid);

            if (!$candidate instanceof Candidate || !isset($inSearch[(int) $candidate->getId()])) {
                $unknown[] = $uuid;
                continue;
            }
            // AU MOINS UN CANAL payé (task `API-018`). Exiger les deux ferait payer deux crédits
            // ce qu'un seul ouvrait hier ; exiger l'identité n'a plus de sens, elle est gratuite.
            if (!($paid[(int) $candidate->getId()] ?? RevealedAccess::none())->anyContact()) {
                $masked[] = $uuid;
                continue;
            }

            $candidates[] = $candidate;
        }

        if ([] !== $unknown) {
            throw new UnprocessableEntityHttpException(\sprintf('Profils absents du jeu de résultats de cette recherche : %s.', implode(', ', $unknown)));
        }
        if ([] !== $masked) {
            throw new UnprocessableEntityHttpException(\sprintf('Profils sans aucune coordonnée dévoilée, donc non exportables : %s.', implode(', ', $masked)));
        }

        return $candidates;
    }

    /** @param list<Candidate> $candidates */
    private function journal(SavedSearch $search, Recruiter $recruiter, array $candidates): void
    {
        $uuids = array_map(
            static fn (Candidate $candidate): string => $candidate->getUuid()->toString(),
            $candidates,
        );

        $this->em->persist(new SearchExport(
            $search,
            $recruiter,
            \count($candidates),
            $uuids,
            new DateTimeImmutable(),
        ));
        $this->em->flush();

        $this->logger->info('Export de profils', [
            'search' => $search->getUuid()->toString(),
            'recruiter' => $recruiter->getUuid()->toString(),
            'organization' => $search->organization->getUuid()->toString(),
            'candidates' => \count($candidates),
        ]);
    }

    private function response(string $csv, string $filename): Response
    {
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', \sprintf('attachment; filename="%s"', $filename));
        // L'export est gratuit : le dire dans la réponse, pas seulement dans l'UI (task 077).
        $response->headers->set('X-Credits-Charged', '0');

        return $response;
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
