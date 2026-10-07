<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Domain\Vocabulary\CompanySectorMap;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateLanguage;
use App\Entity\CandidateSectorExperience;
use App\Entity\Person;
use App\Entity\VocabularyConcept;
use App\Enum\Sector;
use App\Enum\SeniorityLevel;
use App\Enum\SyncStatus;
use App\Repository\CandidateRepository;
use App\Repository\JarviSyncStateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Ingestion descendante du profil pro depuis Jarvi vers la réplica locale (tâche 020).
 * Upsert idempotent ancré sur `jarviProfileId` ; pull incrémental piloté par le curseur `updatedAt`.
 * Jarvi reste maître : on n'écrit ici QUE la copie locale en lecture seule, jamais l'inverse.
 */
final readonly class ProfileIngestor
{
    private const string STREAM = 'profiles';

    /**
     * Décomposition de cette classe, pas dépendance injectée : la projection du parcours était une
     * méthode privée d'ici avant qu'un second appelant ne la réclame (task API-035). La composer
     * dans le constructeur garde le contrat public de l'ingestion inchangé — ses appelants n'ont pas
     * à connaître un collaborateur qu'ils ne choisissent pas.
     */
    private JobProjectionSynchronizer $jobProjections;

    public function __construct(
        private EntityManagerInterface $em,
        private CandidateRepository $candidates,
        private JarviSyncStateRepository $syncStates,
        private JarviClientInterface $client,
        private CompanySectorMap $companySectors,
        private TitleConceptResolver $jobResolver,
        private LoggerInterface $logger = new NullLogger(),
        // L'Unit of Work retient chaque entité ET une copie de ses données d'origine : garder tout
        // le périmètre jusqu'à un flush final épuise la mémoire du processus CLI. On écrit par lots.
        private int $batchSize = 100,
    ) {
        $this->jobProjections = new JobProjectionSynchronizer($em, $jobResolver);
    }

    public function ingestFull(bool $dryRun = false, ?int $limit = null): IngestionReport
    {
        return $this->ingestProfiles($this->client->fetchProfiles(maxProfiles: $limit), null, $dryRun, !$dryRun);
    }

    public function ingestUpdated(bool $dryRun = false, ?DateTimeImmutable $since = null, ?int $limit = null): IngestionReport
    {
        $cursor = $since ?? $this->syncStates->getOrCreate(self::STREAM)->cursor;

        return $this->ingestProfiles($this->client->fetchProfiles($cursor, $limit), $cursor, $dryRun, !$dryRun && null === $since);
    }

    /**
     * Rafraîchit un profil unique (refresh on-demand au dévoilement). Retourne false si introuvable.
     */
    public function refreshOne(string $jarviProfileId, bool $dryRun = false): bool
    {
        $profile = $this->client->fetchProfile($jarviProfileId);
        if (null === $profile) {
            return false;
        }

        $this->upsert($profile, $dryRun);
        if (!$dryRun) {
            $this->em->flush();
        }

        return true;
    }

    /**
     * @param iterable<JarviProfile> $profiles flux paginé : ne jamais le matérialiser en tableau
     */
    private function ingestProfiles(iterable $profiles, ?DateTimeImmutable $initialCursor, bool $dryRun, bool $persistCursor): IngestionReport
    {
        $cursor = $initialCursor;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        /** @var array<string, true> $seenProfileIds */
        $seenProfileIds = [];
        // Une erreur sur un profil ne doit pas invalider tout le lot : on journalise, on continue,
        // et on FIGE le curseur au dernier profil sain (les profils arrivent triés `updatedAt`
        // croissant) afin que le passage suivant reprenne le profil fautif au lieu de le sauter.
        $cursorFrozen = false;
        $pending = 0;
        foreach ($profiles as $profile) {
            try {
                if (isset($seenProfileIds[$profile->jarviProfileId])) {
                    ++$skipped;
                    $cursor = $cursorFrozen ? $cursor : $this->latest($cursor, $profile->updatedAt);
                    $this->logger->warning('Ingestion Jarvi : doublon ignoré dans le lot {jarviProfileId}', [
                        'jarviProfileId' => $profile->jarviProfileId,
                    ]);

                    continue;
                }

                $seenProfileIds[$profile->jarviProfileId] = true;
                $wasCreated = $this->upsert($profile, $dryRun);
                $wasCreated ? ++$created : ++$updated;
                $cursor = $cursorFrozen ? $cursor : $this->latest($cursor, $profile->updatedAt);

                if (!$dryRun && ++$pending >= $this->batchSize) {
                    $this->flushBatch();
                    $pending = 0;
                }
            } catch (Throwable $e) {
                ++$errors;
                $cursorFrozen = true;
                $this->logger->error('Ingestion Jarvi : échec du profil {jarviProfileId}', [
                    'jarviProfileId' => $profile->jarviProfileId,
                    'exception' => $e,
                ]);
            }
        }

        if (!$dryRun) {
            // Dernier lot laissé attaché : sur un import court (tests, `--limit`), l'appelant garde
            // ainsi des entités gérées, comme avant l'introduction des lots.
            $this->em->flush();

            if ($persistCursor) {
                // Relu ici et pas avant la boucle : un `clear()` de lot aurait détaché l'entité.
                $state = $this->syncStates->getOrCreate(self::STREAM);
                $state->cursor = $cursor;
                $state->lastRunAt = new DateTimeImmutable();
                $this->em->flush();
            }
        }

        return new IngestionReport($created, $updated, $skipped, $cursor, $dryRun, $errors);
    }

    /**
     * Écrit le lot courant puis détache tout : sans le `clear()`, l'Unit of Work garderait chaque
     * entité et sa copie d'origine jusqu'à la fin de l'import — c'est ce qui saturait la mémoire
     * sur `--full` (~2 500 profils, trois entités et trois collections chacun).
     */
    private function flushBatch(): void
    {
        $this->em->flush();
        $this->em->clear();
    }

    private function latest(?DateTimeImmutable $cursor, DateTimeImmutable $candidate): DateTimeImmutable
    {
        return null === $cursor || $candidate > $cursor ? $candidate : $cursor;
    }

    /**
     * Crée la projection si absente, sinon rafraîchit la réplica locale. Retourne true si création.
     */
    private function upsert(JarviProfile $profile, bool $dryRun): bool
    {
        $candidate = $this->candidates->findOneByJarviProfileId($profile->jarviProfileId);
        $isNew = !$candidate instanceof Candidate;

        if ($dryRun) {
            return $isNew;
        }

        if ($isNew) {
            // Parser l'ancre AVANT tout persist : un UUID malformé lève ici sans laisser de
            // Person orpheline dans l'Unit of Work (le profil est alors compté en erreur).
            $jarviProfileId = Uuid::fromString($profile->jarviProfileId);

            $person = new Person($profile->firstName, $profile->lastName, country: $profile->country, city: $profile->city);
            $this->em->persist($person);

            $candidate = new Candidate($person, jarviProfileId: $jarviProfileId);
            $this->em->persist($candidate);
        } else {
            $person = $candidate->getPerson();
            $person->givenName = $profile->firstName;
            $person->familyName = $profile->lastName;
            $person->city = $profile->city;
            $person->country = $profile->country;
        }

        // Profil pro = réplica JSON opaque (Jarvi maître), alimenté uniquement par liste blanche.
        $jarvi = $candidate->getJarvi();
        if (!$jarvi instanceof CandidateJarvi) {
            $jarvi = new CandidateJarvi($candidate);
            $candidate->setJarvi($jarvi);
            $this->em->persist($jarvi);
        }
        $jarvi->headline = $profile->headline;
        $jarvi->summary = $profile->summary;
        $jarvi->photoUrl = $profile->photoUrl;
        $jarvi->workExperiences = $profile->workExperiences;
        $jarvi->educations = $profile->educations;
        $jarvi->languages = $profile->languages;
        $jarvi->skills = $profile->skills;
        $jarvi->resumes = $profile->resumes;
        $jarvi->contacts = $profile->contacts;
        $jarvi->experienceYears = ExperienceYearsCalculator::fromPositions($profile->workExperiences);
        $current = CurrentPositionExtractor::fromPositions($profile->workExperiences, $this->jobResolver);
        $jarvi->currentRoleMonths = $current->months;
        $jarvi->currentPositionCertainty = $current->certainty;
        $jarvi->currentTitle = $current->title;
        $jarvi->currentCompanyName = $current->companyName;
        $jarvi->currentJobConcept = null !== $current->conceptId
            ? $this->em->getReference(VocabularyConcept::class, $current->conceptId)
            : null;
        $jarvi->seniority = SeniorityLevel::fromYears($jarvi->experienceYears);
        $jarvi->educationLevel = EducationLevelCalculator::fromEducations($profile->educations);
        // Même valeur projetée sur le candidat, où elle devient filtrable (task 123).
        $candidate->educationLevel = $jarvi->educationLevel;
        $jarvi->rawPayload = $profile->rawPayload;
        // Fraîcheur de la SOURCE (task 116) : jusque-là `updatedAt` ne servait qu'à faire avancer
        // le curseur incrémental, puis était perdu.
        $jarvi->jarviUpdatedAt = $profile->updatedAt;
        $jarvi->lastInteractionAt = $profile->lastInteractionAt;
        $jarvi->lastInteractionType = $profile->lastInteractionType;
        $this->syncSectorExperience($candidate, $profile);
        $this->jobProjections->sync($candidate, $profile->workExperiences);
        $this->syncSpokenLanguages($candidate, $profile);
        $jarvi->syncStatus = SyncStatus::SYNCED;
        $jarvi->syncedAt = new DateTimeImmutable();

        $candidate->active = true;
        $candidate->syncStatus = SyncStatus::SYNCED;
        $candidate->syncedAt = new DateTimeImmutable();

        return $isNew;
    }

    /**
     * Recalcule les langues parlées du candidat (task 114). Mise à jour EN PLACE, pour la même
     * raison que l'expérience par secteur : recréer une ligne `(candidat, langue)` identique
     * violerait la contrainte unique, Doctrine insérant avant de supprimer.
     */
    private function syncSpokenLanguages(Candidate $candidate, JarviProfile $profile): void
    {
        $wanted = SpokenLanguageExtractor::fromLocales($profile->languages);
        $rows = $candidate->getLanguages();

        foreach ($rows as $row) {
            if (\array_key_exists($row->language, $wanted)) {
                $row->proficiency = $wanted[$row->language];
                unset($wanted[$row->language]);
                continue;
            }
            $rows->removeElement($row);
        }

        foreach ($wanted as $code => $level) {
            $rows->add(new CandidateLanguage($candidate, $code, $level));
        }
    }

    /**
     * Recalcule l'expérience par secteur du candidat (task 095).
     *
     * Mise à jour EN PLACE plutôt que purge + réinsertion : Doctrine applique les insertions avant
     * les suppressions, si bien que recréer une ligne `(candidat, secteur)` identique violerait la
     * contrainte unique. On ne supprime donc que les secteurs devenus absents du parcours.
     */
    private function syncSectorExperience(Candidate $candidate, JarviProfile $profile): void
    {
        $wanted = SectorExperienceCalculator::fromPositions($profile->workExperiences, $this->companySectors->all());
        $rows = $candidate->getSectorExperiences();

        foreach ($rows as $row) {
            $sector = $row->sector->value;
            if (isset($wanted[$sector])) {
                $row->months = $wanted[$sector];
                unset($wanted[$sector]);
                continue;
            }
            $rows->removeElement($row);   // secteur disparu du parcours : orphanRemoval le supprime
        }

        foreach ($wanted as $sector => $months) {
            $rows->add(new CandidateSectorExperience($candidate, Sector::from($sector), $months));
        }
    }
}
