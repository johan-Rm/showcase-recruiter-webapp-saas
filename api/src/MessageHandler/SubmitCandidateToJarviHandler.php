<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Jarvi\JarviApplicant;
use App\Domain\Jarvi\JarviApplicantWriterInterface;
use App\Domain\Jarvi\JarviResumeFile;
use App\Enum\SyncStatus;
use App\Message\RefreshCandidateFromJarvi;
use App\Message\SubmitCandidateToJarvi;
use App\Repository\CandidateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Soumet un candidat inscrit à Jarvi via `/applicants` puis ancre sa projection sur le `profileId`
 * réel retourné (tâche 089), afin que l'ingestion quotidienne le rapproche de son profil pro.
 *
 * Non bloquant pour l'inscription (déjà répondue). Un échec Jarvi marque la réplica `FAILED` et
 * relève l'exception : Messenger rejoue le message (retry borné), puis planifie un rafraîchissement
 * différé (tâche 090) le temps de l'extraction asynchrone.
 */
#[AsMessageHandler]
final readonly class SubmitCandidateToJarviHandler
{
    private const string RESUME_DATA_PREFIX = 'data:@file/pdf;base64,';

    public function __construct(
        private CandidateRepository $candidates,
        private JarviApplicantWriterInterface $writer,
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private string $showcaseRecruiterWebappSaasProjectId,
        private int $refreshDelayMs,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(SubmitCandidateToJarvi $message): void
    {
        $candidate = $this->candidates->findOneByUuid($message->candidateId);
        if (null === $candidate) {
            // Candidat supprimé entre l'inscription et le traitement : rien à soumettre.
            return;
        }

        $jarvi = $candidate->getJarvi();
        $person = $candidate->getPerson();

        try {
            $result = $this->writer->submit(new JarviApplicant(
                externalId: $person->getUuid()->toString(),
                firstName: $person->givenName,
                lastName: $person->familyName,
                projectId: $this->showcaseRecruiterWebappSaasProjectId,
                linkedinUrl: $person->linkedinUrl,
                emailAddresses: $person->contactEmail,
                location: $this->location($person->city, $person->country),
                resumesFiles: $this->resumes($message),
            ));

            // Ancre stable = le profileId Jarvi réel (remplace l'UUIDv7 local minté à l'inscription).
            $candidate->jarviProfileId = Uuid::fromString($result->profileId);
            $this->em->flush();

            if ([] !== $result->fileErrors) {
                $this->logger->warning('Applicant Jarvi soumis avec des CV en erreur', [
                    'candidateId' => $message->candidateId,
                    'fileErrors' => $result->fileErrors,
                ]);
            }

            // Enrichissement asynchrone côté Jarvi : on planifie un refresh différé plutôt que
            // d'attendre l'import du lendemain (tâche 090).
            $this->bus->dispatch(new RefreshCandidateFromJarvi($message->candidateId), [new DelayStamp($this->refreshDelayMs)]);
        } catch (Throwable $e) {
            if (null !== $jarvi) {
                $jarvi->syncStatus = SyncStatus::FAILED;
                $this->em->flush();
            }
            $this->logger->error('Soumission applicant Jarvi échouée', [
                'candidateId' => $message->candidateId,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @return list<JarviResumeFile>
     */
    private function resumes(SubmitCandidateToJarvi $message): array
    {
        if (null === $message->cvBase64 || '' === $message->cvBase64) {
            return [];
        }

        return [new JarviResumeFile(
            $message->cvFileName ?? 'cv.pdf',
            self::RESUME_DATA_PREFIX.$message->cvBase64,
        )];
    }

    private function location(?string $city, ?string $country): ?string
    {
        $parts = array_values(array_filter([$city, $country], static fn (?string $p): bool => null !== $p && '' !== $p));

        return [] === $parts ? null : implode(', ', $parts);
    }
}
