<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Candidate\RevealedAccess;
use App\Entity\Candidate;
use App\Entity\Person;
use App\Enum\ConsentSource;
use App\Enum\EducationLevel;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use App\State\CandidateMapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Recherche par niveau d'études (task 123, volet B).
 *
 * Le niveau est dérivé à l'ingestion (task 112) mais restait bloqué dans la réplica Jarvi. Projeté
 * sur le candidat, il devient filtrable. Le filtre est « au moins » (comme la langue) et EXCLUT les
 * profils sans niveau reconnu : on ne suppose jamais un diplôme absent.
 */
final class CandidateEducationLevelSearchTest extends ApiTestCase
{
    public function testFiltersAtLeastAndExcludesUnknownLevels(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        // Candidats maîtrisés (le vivier de test est minimal) : un par niveau + un sans niveau.
        $bac = $this->persistSearchable($em, EducationLevel::BAC);
        $licence = $this->persistSearchable($em, EducationLevel::LICENCE);
        $master = $this->persistSearchable($em, EducationLevel::MASTER);
        $doctorat = $this->persistSearchable($em, EducationLevel::DOCTORAT);
        $none = $this->persistSearchable($em, null);
        $em->flush();

        $ids = $repository->matchingIds(new CandidateSearchCriteria(educationLevel: EducationLevel::LICENCE));

        // « au moins licence » : licence, master (rang égal ingénieur) et doctorat remontent…
        self::assertContains($licence->getId(), $ids);
        self::assertContains($master->getId(), $ids);
        self::assertContains($doctorat->getId(), $ids);
        // …le bac (rang inférieur) et l'absence de niveau sont exclus.
        self::assertNotContains($bac->getId(), $ids, 'un niveau inférieur ne passe pas le seuil');
        self::assertNotContains($none->getId(), $ids, 'un niveau absent n’est jamais supposé');
    }

    public function testFicheExposesTheNormalizedLevelIncludingWhenAbsent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        // Profils réellement ingérés (réplica Jarvi présente) pour exercer le mapping de la fiche.
        [$pool] = $repository->search(new CandidateSearchCriteria(perPage: 2));
        [$withLevel, $without] = $pool;
        $withLevel->educationLevel = EducationLevel::MASTER;
        $without->educationLevel = null;
        $em->flush();

        // Le niveau normalisé sort MÊME masqué : il décrit sans nommer, comme les années d'expérience.
        self::assertSame('MASTER', CandidateMapper::toDetail($withLevel, RevealedAccess::none())->educationLevel);
        self::assertNull(CandidateMapper::toDetail($without, RevealedAccess::none())->educationLevel);
    }

    private function persistSearchable(EntityManagerInterface $em, ?EducationLevel $level): Candidate
    {
        $person = new Person('Test', 'Etudes-'.uniqid(), contactEmail: uniqid('edu-', true).'@example.test', country: 'FR', city: 'Paris');
        $em->persist($person);

        $candidate = new Candidate(
            $person,
            jarviProfileId: Uuid::v7(),
            visibility: ProfileVisibility::VISIBLE,
            syncStatus: SyncStatus::SYNCED,
            syncedAt: new DateTimeImmutable(),
        );
        $candidate->giveConsent(ConsentSource::ONBOARDING, new DateTimeImmutable());
        $candidate->educationLevel = $level;
        $em->persist($candidate);

        return $candidate;
    }
}
