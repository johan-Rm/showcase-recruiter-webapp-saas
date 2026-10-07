<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Jarvi\ProfileIngestor;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Enum\SyncStatus;
use App\Message\RefreshCandidateFromJarvi;
use App\Repository\CandidateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Rafraîchit la réplica d'un candidat depuis Jarvi après soumission `/applicants` (tâche 090).
 * L'extraction Jarvi est asynchrone : positions/skills n'arrivent qu'après quelques minutes. On
 * tente donc un refresh ciblé, borné et espacé, jusqu'à ce que le profil soit enrichi (`SYNCED`)
 * ou que les tentatives soient épuisées (`FAILED`, requalifiable), sans attendre l'import quotidien.
 */
#[AsMessageHandler]
final readonly class RefreshCandidateFromJarviHandler
{
    public function __construct(
        private CandidateRepository $candidates,
        private ProfileIngestor $ingestor,
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private int $maxAttempts,
        private int $delayMs,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(RefreshCandidateFromJarvi $message): void
    {
        $candidate = $this->candidates->findOneByUuid($message->candidateId);
        if (null === $candidate) {
            return;
        }

        $found = $this->ingestor->refreshOne($candidate->jarviProfileId->toString());
        $jarvi = $candidate->getJarvi();

        // `refreshOne` marque `SYNCED` dès qu'il trouve le profil, même vide : on ne conclut à
        // l'enrichissement que si positions/skills sont réellement présents.
        if ($found && $this->isEnriched($jarvi)) {
            $this->logger->info('Enrichissement Jarvi abouti', [
                'candidateId' => $message->candidateId,
                'attempt' => $message->attempt,
            ]);

            return;
        }

        if ($message->attempt < $this->maxAttempts) {
            $this->markStatus($candidate, SyncStatus::PENDING);
            $this->bus->dispatch(
                new RefreshCandidateFromJarvi($message->candidateId, $message->attempt + 1),
                [new DelayStamp($this->delayMs)],
            );
            $this->logger->info('Enrichissement Jarvi pas encore prêt, nouvelle tentative planifiée', [
                'candidateId' => $message->candidateId,
                'attempt' => $message->attempt,
            ]);

            return;
        }

        $this->markStatus($candidate, SyncStatus::FAILED);
        $this->logger->warning('Enrichissement Jarvi abandonné après épuisement des tentatives', [
            'candidateId' => $message->candidateId,
            'attempt' => $message->attempt,
        ]);
    }

    private function isEnriched(?CandidateJarvi $jarvi): bool
    {
        return null !== $jarvi && ([] !== $jarvi->workExperiences || [] !== $jarvi->skills);
    }

    private function markStatus(Candidate $candidate, SyncStatus $status): void
    {
        $candidate->syncStatus = $status;
        $jarvi = $candidate->getJarvi();
        if (null !== $jarvi) {
            $jarvi->syncStatus = $status;
        }
        $this->em->flush();
    }
}
