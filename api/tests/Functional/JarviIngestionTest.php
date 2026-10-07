<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Candidate\RevealedAccess;
use App\Domain\Jarvi\JarviClientInterface;
use App\Domain\Jarvi\JarviProfile;
use App\Domain\Jarvi\PerimeterReconciler;
use App\Domain\Jarvi\ProfileIngestor;
use App\Domain\Vocabulary\CompanySectorMap;
use App\Domain\Vocabulary\JobConceptMap;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Entity\Candidate;
use App\Repository\CandidateRepository;
use App\Repository\JarviSyncStateRepository;
use App\State\CandidateMapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Ingestion Jarvi (tâche 020) avec client mocké : upsert idempotent (création + mise à jour),
 * avancée du curseur, et désactivation des sorties de périmètre (jamais de suppression).
 */
final class JarviIngestionTest extends ApiTestCase
{
    public function testIngestCreatesNewAndUpdatesExisting(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $existing = $candidates->findActive()[0];
        $existingId = $existing->jarviProfileId->toString();
        $newId = Uuid::v7()->toString();

        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($existingId, 'Renamed', 'Profile', 'Nouveau titre', 'Tanger', 'MA', new DateTimeImmutable('2026-06-20 10:00:00')),
                new JarviProfile($newId, 'Nouveau', 'Profil', 'À recruter', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
            ],
            perimeter: [],
        );

        $report = (new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestUpdated();

        self::assertSame(1, $report->created);
        self::assertSame(1, $report->updated);
        self::assertEquals(new DateTimeImmutable('2026-06-21 10:00:00'), $report->cursor);

        self::assertNotNull($candidates->findOneByJarviProfileId($newId));
        self::assertSame('Renamed', $existing->getPerson()->givenName);
        self::assertSame('Nouveau titre', $existing->getJarvi()?->headline);
    }

    public function testFullImportIsIdempotentOnRerun(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $newId = Uuid::v7()->toString();
        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($newId, 'Idem', 'Potent', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
            ],
            perimeter: [],
        );
        $ingestor = new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection())));

        $first = $ingestor->ingestFull();
        self::assertSame(1, $first->created);

        // Relancer ne doit rien dupliquer : le profil est retrouvé par son ancre et seulement mis à jour.
        $second = $ingestor->ingestFull();
        self::assertSame(0, $second->created);
        self::assertSame(1, $second->updated);
        self::assertNotNull($candidates->findOneByJarviProfileId($newId));
    }

    /**
     * Régression mémoire du `--full` : l'import écrit par lots et vide l'Unit of Work en chemin.
     * Sans cela, chaque entité ingérée — et la copie de ses données conservée pour le change-set —
     * reste en mémoire jusqu'au flush final, ce qui épuise les 128 M du conteneur preprod.
     */
    public function testImportFlushesByBatchesAndStillPersistsCursor(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $ids = [];
        $profiles = [];
        foreach (range(1, 5) as $rank) {
            $ids[] = $id = Uuid::v7()->toString();
            $profiles[] = new JarviProfile($id, 'Lot', 'Profil'.$rank, 'Titre', 'Rabat', 'MA', new DateTimeImmutable(\sprintf('2026-06-21 10:0%d:00', $rank)));
        }

        $ingestor = new ProfileIngestor($em, $candidates, $syncStates, $this->fakeClient($profiles, []), new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection())), batchSize: 2);
        $report = $ingestor->ingestUpdated();

        self::assertSame(5, $report->created);
        // Cinq profils par lots de deux : seul le dernier lot est encore attaché. Sans vidage,
        // l'Unit of Work porterait les cinq (Person + Candidate + CandidateJarvi chacun).
        self::assertLessThan(10, $em->getUnitOfWork()->size());

        foreach ($ids as $id) {
            self::assertNotNull($candidates->findOneByJarviProfileId($id));
        }

        // Le curseur est relu après les vidages de lot : il doit malgré tout atteindre la base.
        $em->clear();
        self::assertEquals(new DateTimeImmutable('2026-06-21 10:05:00'), $syncStates->getOrCreate('profiles')->cursor);
    }

    public function testDomainIngestionDoesNotGrantConsentImplicitly(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $newId = Uuid::v7()->toString();
        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($newId, 'Sans', 'Consentement', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
            ],
            perimeter: [],
        );

        (new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull();

        $candidate = $candidates->findOneByJarviProfileId($newId);
        self::assertNotNull($candidate);
        self::assertFalse($candidate->hasConsent());
    }

    public function testFullImportSkipsDuplicateProfilesInSameBatch(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $newId = Uuid::v7()->toString();
        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($newId, 'Duplicate', 'First', 'Premier titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
                new JarviProfile($newId, 'Duplicate', 'Second', 'Doublon ignoré', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:01:00')),
            ],
            perimeter: [],
        );

        $report = (new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull();

        self::assertSame(1, $report->created);
        self::assertSame(0, $report->updated);
        self::assertSame(1, $report->skipped);
        self::assertEquals(new DateTimeImmutable('2026-06-21 10:01:00'), $report->cursor);
        self::assertSame('Duplicate', $candidates->findOneByJarviProfileId($newId)?->getPerson()->givenName);
    }

    public function testRefreshOneUpdatesTargetedProfile(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $existing = $candidates->findActive()[0];
        $existingId = $existing->jarviProfileId->toString();

        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($existingId, 'Refreshed', 'One', 'Titre rafraîchi', 'Casablanca', 'MA', new DateTimeImmutable('2026-06-25 10:00:00')),
            ],
            perimeter: [],
        );
        $ingestor = new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection())));

        self::assertTrue($ingestor->refreshOne($existingId));
        self::assertSame('Refreshed', $existing->getPerson()->givenName);
        self::assertSame('Titre rafraîchi', $existing->getJarvi()?->headline);

        self::assertFalse($ingestor->refreshOne(Uuid::v7()->toString()));
    }

    public function testIngestionPreservesBlockSourceOnPositionsAndEducations(): void
    {
        // Tâche 092 : la provenance (`source`) portée par chaque position/formation Jarvi
        // (LINKEDIN / RESUME / MANUAL) doit rester dans la réplica, sans toucher les skills.
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $newId = Uuid::v7()->toString();
        $profile = new JarviProfile(
            $newId,
            'Source',
            'Preserved',
            'Titre',
            'Rabat',
            'MA',
            new DateTimeImmutable('2026-06-21 10:00:00'),
            workExperiences: [
                ['title' => 'Développeur', 'companyName' => 'ACME', 'source' => 'LINKEDIN'],
                ['title' => 'Consultant', 'companyName' => 'Beta', 'source' => 'RESUME'],
            ],
            educations: [
                ['title' => 'Master', 'source' => 'MANUAL'],
            ],
            skills: ['PHP', 'SQL'],
        );

        (new ProfileIngestor($em, $candidates, $syncStates, $this->fakeClient([$profile], []), new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull();

        $jarvi = $candidates->findOneByJarviProfileId($newId)?->getJarvi();
        self::assertNotNull($jarvi);
        self::assertSame('LINKEDIN', $jarvi->workExperiences[0]['source']);
        self::assertSame('RESUME', $jarvi->workExperiences[1]['source']);
        self::assertSame('MANUAL', $jarvi->educations[0]['source']);
        // Les skills restent un tableau de chaînes (structure inchangée, la recherche en dépend).
        self::assertSame(['PHP', 'SQL'], $jarvi->skills);
    }

    /**
     * Fraîcheur (task 116) : la date de modification vient de la SOURCE et ne doit jamais être
     * confondue avec `syncedAt`, qui date notre lecture — sinon toute donnée ancienne passerait pour
     * fraîche dès qu'on la relit.
     */
    /**
     * Dernier contact (task 121) : la date ET son canal sont conservés, et restent visibles sur un
     * profil MASQUÉ — c'est avant de dépenser un dévoilement qu'un recruteur doit savoir qu'un
     * confrère est déjà passé. Absence non inventée : la majorité du vivier n'a jamais été approchée.
     */
    public function testKeepsLastInteractionAndExposesItEvenWhenMasked(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $contacted = Uuid::v7()->toString();
        $never = Uuid::v7()->toString();
        $lastContact = new DateTimeImmutable('2026-07-24 09:30:00');

        (new ProfileIngestor($em, $candidates, $syncStates, $this->fakeClient([
            new JarviProfile($contacted, 'Deja', 'Contacte', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00'), lastInteractionAt: $lastContact, lastInteractionType: 'WHATSAPP_RECEIVED'),
            new JarviProfile($never, 'Jamais', 'Approche', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
        ], []), new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull();

        $jarvi = $candidates->findOneByJarviProfileId($contacted)?->getJarvi();
        self::assertNotNull($jarvi);
        self::assertSame($lastContact->format('Y-m-d H:i'), $jarvi->lastInteractionAt?->format('Y-m-d H:i'));
        self::assertSame('WHATSAPP_RECEIVED', $jarvi->lastInteractionType);

        // Jamais approché : reste vide, aucune date inventée.
        $untouched = $candidates->findOneByJarviProfileId($never)?->getJarvi();
        self::assertNull($untouched?->lastInteractionAt);
        self::assertNull($untouched?->lastInteractionType);

        // Visible MÊME masqué : c'est tout l'intérêt du signal.
        $card = CandidateMapper::toCard($candidates->findOneByJarviProfileId($contacted), RevealedAccess::none());
        self::assertFalse($card->revealedEmail);
        self::assertSame($lastContact->format('Y-m-d H:i'), $card->lastInteractionAt?->format('Y-m-d H:i'));
        self::assertSame('WHATSAPP_RECEIVED', $card->lastInteractionType);
    }

    public function testKeepsTheSourceUpdatedAtApartFromOurSyncDate(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $newId = Uuid::v7()->toString();
        $updatedAt = new DateTimeImmutable('2026-02-14 08:30:00');
        $profile = new JarviProfile($newId, 'Fraîcheur', 'Source', 'Titre', 'Rabat', 'MA', $updatedAt);

        (new ProfileIngestor($em, $candidates, $syncStates, $this->fakeClient([$profile], []), new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull();

        $jarvi = $candidates->findOneByJarviProfileId($newId)?->getJarvi();
        self::assertNotNull($jarvi);
        self::assertNotNull($jarvi->jarviUpdatedAt);
        self::assertSame($updatedAt->format('Y-m-d H:i:s'), $jarvi->jarviUpdatedAt->format('Y-m-d H:i:s'));
        self::assertNotSame(
            $jarvi->jarviUpdatedAt->format('Y-m-d'),
            $jarvi->syncedAt?->format('Y-m-d'),
            'la date de la source ne doit pas être écrasée par la date de synchronisation',
        );
    }

    public function testPartialErrorIsCountedAndFreezesCursor(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $goodBeforeId = Uuid::v7()->toString();
        $goodAfterId = Uuid::v7()->toString();
        // Profils triés `updatedAt` croissant : sain → fautif (ancre malformée) → sain.
        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($goodBeforeId, 'Avant', 'Erreur', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-20 10:00:00')),
                new JarviProfile('pas-un-uuid', 'Profil', 'Fautif', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
                new JarviProfile($goodAfterId, 'Apres', 'Erreur', 'Titre', 'Rabat', 'MA', new DateTimeImmutable('2026-06-22 10:00:00')),
            ],
            perimeter: [],
        );

        $report = (new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull();

        // L'erreur n'invalide pas le lot : les deux profils sains sont importés, le fautif est compté.
        self::assertSame(1, $report->errors);
        self::assertSame(2, $report->created);
        self::assertNotNull($candidates->findOneByJarviProfileId($goodBeforeId));
        self::assertNotNull($candidates->findOneByJarviProfileId($goodAfterId));
        // Curseur figé au dernier profil sain AVANT l'erreur → le passage suivant reprend le fautif.
        self::assertEquals(new DateTimeImmutable('2026-06-20 10:00:00'), $report->cursor);
    }

    public function testReconcileDeactivatesProfilesOutsidePerimeter(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);

        $active = $candidates->findActive();
        $kept = $active[0]->jarviProfileId->toString();

        $client = $this->fakeClient(profiles: [], perimeter: [$kept]);

        $report = (new PerimeterReconciler($em, $candidates, $client))->reconcile();

        self::assertSame(1, $report->perimeterSize);
        self::assertSame(\count($active) - 1, $report->deactivated);
        self::assertCount(1, $candidates->findActive());
    }

    public function testReconcileSkipsWhenPerimeterIsEmpty(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);

        $before = \count($candidates->findActive());
        $client = $this->fakeClient(profiles: [], perimeter: []);

        $report = (new PerimeterReconciler($em, $candidates, $client))->reconcile();

        self::assertSame(0, $report->deactivated);
        self::assertCount($before, $candidates->findActive());
    }

    public function testDryRunDoesNotPersistImportedProfiles(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);
        $syncStates = $container->get(JarviSyncStateRepository::class);

        $newId = Uuid::v7()->toString();
        $client = $this->fakeClient(
            profiles: [
                new JarviProfile($newId, 'Dry', 'Run', 'Simulation', 'Rabat', 'MA', new DateTimeImmutable('2026-06-21 10:00:00')),
            ],
            perimeter: [],
        );

        $report = (new ProfileIngestor($em, $candidates, $syncStates, $client, new CompanySectorMap($em->getConnection()), new TitleConceptResolver(new JobConceptMap($em->getConnection()))))->ingestFull(dryRun: true);

        self::assertTrue($report->dryRun);
        self::assertSame(1, $report->created);
        self::assertNull($candidates->findOneByJarviProfileId($newId));
    }

    public function testReconcileReactivatesProfilesReturningToPerimeter(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $candidates = $container->get(CandidateRepository::class);

        // Un profil actif qu'on simule « sorti du périmètre » (désactivé).
        $returning = $candidates->findActive()[0];
        $returning->active = false;
        $em->flush();
        $returningId = $returning->jarviProfileId->toString();

        // Périmètre courant = profils restés actifs + celui qui revient.
        $perimeter = array_map(
            static fn (Candidate $c): string => $c->jarviProfileId->toString(),
            $candidates->findActive(),
        );
        $perimeter[] = $returningId;

        $client = $this->fakeClient(profiles: [], perimeter: $perimeter);
        $report = (new PerimeterReconciler($em, $candidates, $client))->reconcile();

        self::assertSame(0, $report->deactivated);
        self::assertSame(1, $report->reactivated);
        self::assertTrue($returning->active);
    }

    /**
     * @param list<JarviProfile> $profiles
     * @param list<string>       $perimeter
     */
    private function fakeClient(array $profiles, array $perimeter): JarviClientInterface
    {
        return new class($profiles, $perimeter) implements JarviClientInterface {
            /** @var list<JarviProfile> */
            private array $profiles;
            /** @var list<string> */
            private array $perimeter;

            /**
             * @param list<JarviProfile> $profiles
             * @param list<string>       $perimeter
             */
            public function __construct(array $profiles, array $perimeter)
            {
                $this->profiles = $profiles;
                $this->perimeter = $perimeter;
            }

            public function fetchProfiles(?DateTimeImmutable $since = null, ?int $maxProfiles = null): iterable
            {
                // Générateur comme le vrai client : l'ingestion doit tenir sans jamais réembobiner.
                yield from null === $maxProfiles ? $this->profiles : \array_slice($this->profiles, 0, $maxProfiles);
            }

            public function listPerimeterProfileIds(?int $maxProfiles = null): array
            {
                return null === $maxProfiles ? $this->perimeter : \array_slice($this->perimeter, 0, $maxProfiles);
            }

            public function fetchProfile(string $jarviProfileId): ?JarviProfile
            {
                foreach ($this->profiles as $profile) {
                    if ($profile->jarviProfileId === $jarviProfileId) {
                        return $profile;
                    }
                }

                return null;
            }
        };
    }
}
