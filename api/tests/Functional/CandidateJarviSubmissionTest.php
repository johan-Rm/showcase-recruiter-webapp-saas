<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Candidate\CandidateOnboarder;
use App\Domain\Jarvi\JarviApplicant;
use App\Domain\Jarvi\JarviApplicantResult;
use App\Domain\Jarvi\JarviApplicantWriterInterface;
use App\Domain\Jarvi\JarviClientInterface;
use App\Domain\Jarvi\JarviProfile;
use App\Domain\Jarvi\JarviWriteException;
use App\Domain\Jarvi\ProfileIngestor;
use App\Domain\Vocabulary\CompanySectorMap;
use App\Domain\Vocabulary\JobConceptMap;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Entity\Candidate;
use App\Enum\SyncStatus;
use App\Message\RefreshCandidateFromJarvi;
use App\Message\SubmitCandidateToJarvi;
use App\MessageHandler\RefreshCandidateFromJarviHandler;
use App\MessageHandler\SubmitCandidateToJarviHandler;
use App\Repository\CandidateRepository;
use App\Repository\JarviSyncStateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Remontée candidat → Jarvi (tâches 089/090) : ancrage sur le profileId réel, dégradé résilient si
 * Jarvi KO, idempotence, puis convergence du statut d'enrichissement (PENDING → SYNCED/FAILED).
 */
final class CandidateJarviSubmissionTest extends ApiTestCase
{
    private const string JARVI_PROJECT = '00000000-0000-4000-8000-000000000001';

    public function testSubmitAnchorsCandidateOnReturnedProfileId(): void
    {
        $candidate = $this->onboard('anchor.candidate@example.com');
        $localAnchor = $candidate->jarviProfileId->toString();
        $jarviProfileId = Uuid::v7()->toString();

        $writer = new CapturingJarviApplicantWriter($jarviProfileId);
        $bus = new RecordingMessageBus();
        $this->submitHandler($writer, $bus)(new SubmitCandidateToJarvi($candidate->getUuid()->toString()));

        self::assertSame($jarviProfileId, $candidate->jarviProfileId->toString());
        self::assertNotSame($localAnchor, $candidate->jarviProfileId->toString());

        $applicant = $writer->last();
        self::assertNotNull($applicant);
        self::assertSame($candidate->getPerson()->getUuid()->toString(), $applicant->externalId);
        self::assertSame(self::JARVI_PROJECT, $applicant->projectId);
        // Un refresh différé est planifié (tâche 090).
        self::assertCount(1, $bus->dispatched);
        self::assertInstanceOf(RefreshCandidateFromJarvi::class, $bus->dispatched[0]);
    }

    public function testSubmitCarriesResumeWhenProvided(): void
    {
        $candidate = $this->onboard('resume.candidate@example.com');
        $writer = new CapturingJarviApplicantWriter(Uuid::v7()->toString());

        $this->submitHandler($writer, new RecordingMessageBus())(
            new SubmitCandidateToJarvi($candidate->getUuid()->toString(), 'mon-cv.pdf', 'QUJD'),
        );

        $applicant = $writer->last();
        self::assertNotNull($applicant);
        self::assertCount(1, $applicant->resumesFiles);
        self::assertSame('mon-cv.pdf', $applicant->resumesFiles[0]->fileName);
        self::assertSame('data:@file/pdf;base64,QUJD', $applicant->resumesFiles[0]->data);
    }

    public function testSubmitFailureMarksFailedAndRethrows(): void
    {
        $candidate = $this->onboard('ko.candidate@example.com');
        $handler = $this->submitHandler(new ThrowingJarviApplicantWriter(), new RecordingMessageBus());

        try {
            $handler(new SubmitCandidateToJarvi($candidate->getUuid()->toString()));
            self::fail('Une exception Jarvi était attendue (déclenche le retry Messenger).');
        } catch (JarviWriteException) {
            // Attendu : Messenger rejouera le message.
        }

        self::assertSame(SyncStatus::FAILED, $candidate->getJarvi()?->syncStatus);
    }

    public function testResubmitIsIdempotentOnExternalId(): void
    {
        $candidate = $this->onboard('idem.candidate@example.com');
        $jarviProfileId = Uuid::v7()->toString();
        $writer = new CapturingJarviApplicantWriter($jarviProfileId);

        $handler = $this->submitHandler($writer, new RecordingMessageBus());
        $handler(new SubmitCandidateToJarvi($candidate->getUuid()->toString()));
        $handler(new SubmitCandidateToJarvi($candidate->getUuid()->toString()));

        // Même externalId → même profileId Jarvi : l'ancre ne dérive pas, pas de doublon.
        self::assertSame($jarviProfileId, $candidate->jarviProfileId->toString());
        self::assertSame($candidate->getPerson()->getUuid()->toString(), $writer->last()?->externalId);
    }

    public function testRefreshMarksSyncedWhenEnriched(): void
    {
        $candidate = $this->onboard('enriched.candidate@example.com');
        $id = $candidate->jarviProfileId->toString();
        $profile = new JarviProfile($id, 'Nadia', 'Berrada', 'Titre', 'Rabat', 'MA', new DateTimeImmutable(), workExperiences: [['title' => 'Dev', 'source' => 'LINKEDIN']], skills: ['PHP']);

        $bus = new RecordingMessageBus();
        $this->refreshHandler($profile, $bus, maxAttempts: 3)(new RefreshCandidateFromJarvi($candidate->getUuid()->toString()));

        self::assertSame(SyncStatus::SYNCED, $candidate->getJarvi()?->syncStatus);
        self::assertCount(0, $bus->dispatched);
    }

    public function testRefreshRetriesWhenNotYetEnriched(): void
    {
        $candidate = $this->onboard('pending.candidate@example.com');
        $id = $candidate->jarviProfileId->toString();
        // Profil trouvé mais vide (extraction pas encore arrivée) → nouvelle tentative planifiée.
        $profile = new JarviProfile($id, 'Nadia', 'Berrada', 'Titre', 'Rabat', 'MA', new DateTimeImmutable());

        $bus = new RecordingMessageBus();
        $this->refreshHandler($profile, $bus, maxAttempts: 3)(new RefreshCandidateFromJarvi($candidate->getUuid()->toString(), 1));

        self::assertSame(SyncStatus::PENDING, $candidate->getJarvi()?->syncStatus);
        self::assertCount(1, $bus->dispatched);
        self::assertInstanceOf(RefreshCandidateFromJarvi::class, $bus->dispatched[0]);
        self::assertSame(2, $bus->dispatched[0]->attempt);
    }

    public function testRefreshFailsAfterMaxAttempts(): void
    {
        $candidate = $this->onboard('exhausted.candidate@example.com');
        $id = $candidate->jarviProfileId->toString();
        $profile = new JarviProfile($id, 'Nadia', 'Berrada', 'Titre', 'Rabat', 'MA', new DateTimeImmutable());

        $bus = new RecordingMessageBus();
        $this->refreshHandler($profile, $bus, maxAttempts: 3)(new RefreshCandidateFromJarvi($candidate->getUuid()->toString(), 3));

        self::assertSame(SyncStatus::FAILED, $candidate->getJarvi()?->syncStatus);
        self::assertCount(0, $bus->dispatched);
    }

    private function onboard(string $email): Candidate
    {
        return self::getContainer()->get(CandidateOnboarder::class)->onboard(
            $email,
            'password123',
            'Nadia',
            'Berrada',
            'https://www.linkedin.com/in/nadia-berrada',
            'MA',
            'Rabat',
        );
    }

    private function submitHandler(JarviApplicantWriterInterface $writer, MessageBusInterface $bus): SubmitCandidateToJarviHandler
    {
        $container = self::getContainer();

        return new SubmitCandidateToJarviHandler(
            $container->get(CandidateRepository::class),
            $writer,
            $container->get(EntityManagerInterface::class),
            $bus,
            self::JARVI_PROJECT,
            1000,
            new NullLogger(),
        );
    }

    private function refreshHandler(JarviProfile $profile, MessageBusInterface $bus, int $maxAttempts): RefreshCandidateFromJarviHandler
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $client = $this->fakeClient($profile);
        $ingestor = new ProfileIngestor($em, $candidates, $container->get(JarviSyncStateRepository::class), $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection())));

        return new RefreshCandidateFromJarviHandler($candidates, $ingestor, $em, $bus, $maxAttempts, 1000, new NullLogger());
    }

    private function fakeClient(JarviProfile $profile): JarviClientInterface
    {
        return new class($profile) implements JarviClientInterface {
            public function __construct(private readonly JarviProfile $profile)
            {
            }

            public function fetchProfiles(?DateTimeImmutable $since = null, ?int $maxProfiles = null): iterable
            {
                return [];
            }

            public function listPerimeterProfileIds(?int $maxProfiles = null): array
            {
                return [];
            }

            public function fetchProfile(string $jarviProfileId): ?JarviProfile
            {
                return $jarviProfileId === $this->profile->jarviProfileId ? $this->profile : null;
            }
        };
    }
}

final class CapturingJarviApplicantWriter implements JarviApplicantWriterInterface
{
    private ?JarviApplicant $last = null;

    public function __construct(private readonly string $profileId)
    {
    }

    public function submit(JarviApplicant $applicant): JarviApplicantResult
    {
        $this->last = $applicant;

        return new JarviApplicantResult($this->profileId, 'task-1', []);
    }

    public function last(): ?JarviApplicant
    {
        return $this->last;
    }
}

final class ThrowingJarviApplicantWriter implements JarviApplicantWriterInterface
{
    public function submit(JarviApplicant $applicant): JarviApplicantResult
    {
        throw new JarviWriteException('Jarvi indisponible');
    }
}

final class RecordingMessageBus implements MessageBusInterface
{
    /** @var list<object> */
    public array $dispatched = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;

        return new Envelope($message, $stamps);
    }
}
