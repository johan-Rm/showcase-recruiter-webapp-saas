<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobExperience;
use App\Entity\CandidateJobTitle;
use App\Entity\Person;
use App\Entity\VocabularyConcept;
use App\Enum\ConsentSource;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Enum\VocabularyType;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * La borne d'expérience compte-t-elle la carrière, ou le métier cherché ?
 *
 * Les deux, selon ce que la recherche nomme. Sans métier, elle compte la carrière — un profil vaut
 * par son ancienneté. Avec un métier, elle compte les mois exercés DANS ce métier : « architecte,
 * 5 ans » cessait autrement de vouloir dire quoi que ce soit, en retenant un architecte de fraîche
 * date qui a fait dix ans d'autre chose.
 *
 * Le REPLI garde la carrière, et c'est le cas qu'il ne faut pas casser : un terme que le
 * dictionnaire ignore n'a aucune durée rattachée, et compter zéro mois ne ramènerait plus personne.
 */
final class CandidateJobExperienceMinTest extends ApiTestCase
{
    public function testWithAJobTheBoundCountsMonthsInThatJob(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $label = 'Ergonome '.uniqid();
        $concept = $this->concept($em, $label);

        // Même carrière, deux ancienneté différentes DANS le métier cherché.
        $veteran = $this->exercising($em, $concept, monthsInJob: 84, careerYears: 12);
        $newcomer = $this->exercising($em, $concept, monthsInJob: 24, careerYears: 12);
        $em->flush();

        $ids = $repository->matchingIds(new CandidateSearchCriteria(job: $label, experienceMin: 5));

        self::assertContains($veteran->getId(), $ids, 'sept ans dans le métier passent le seuil de cinq');
        self::assertNotContains(
            $newcomer->getId(),
            $ids,
            'douze ans de carrière ne compensent pas deux ans dans le métier cherché',
        );
    }

    public function testWithoutAJobTheBoundStillCountsTheWholeCareer(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $concept = $this->concept($em, 'Ergonome '.uniqid());

        $newcomer = $this->exercising($em, $concept, monthsInJob: 24, careerYears: 12);
        $em->flush();

        $ids = $repository->matchingIds(new CandidateSearchCriteria(experienceMin: 5));

        // LA MÊME personne, la même borne, et elle passe : c'est la question qui a changé.
        self::assertContains($newcomer->getId(), $ids);
    }

    public function testTheBoundIsIgnoredOnAJobTheDictionaryDoesNotKnow(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $rawTitle = 'Charpentier de marine '.uniqid();

        // Aucun concept ne porte ce libellé : seul le repli sur l'intitulé écrit peut le retrouver.
        $carpenter = $this->exercising($em, null, monthsInJob: 0, careerYears: 12, rawTitle: $rawTitle);
        $junior = $this->exercising($em, null, monthsInJob: 0, careerYears: 2, rawTitle: $rawTitle);
        $em->flush();

        $ids = $repository->matchingIds(new CandidateSearchCriteria(job: $rawTitle, experienceMin: 5));

        // Faute de durée à compter, la borne retombe sur la carrière — sinon ce terme, posé avec une
        // exigence d'expérience, ne ramènerait plus jamais personne.
        self::assertContains($carpenter->getId(), $ids, 'douze ans de carrière passent le seuil');
        self::assertNotContains($junior->getId(), $ids, 'la borne de carrière s’applique quand même');
    }

    public function testAJobWithoutABoundIsUnchanged(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $label = 'Ergonome '.uniqid();
        $concept = $this->concept($em, $label);

        $newcomer = $this->exercising($em, $concept, monthsInJob: 3, careerYears: 1);
        $em->flush();

        $ids = $repository->matchingIds(new CandidateSearchCriteria(job: $label));

        // Sans borne, la présence du métier suffit : trois mois exercés restent une correspondance.
        self::assertContains($newcomer->getId(), $ids);
    }

    private function concept(EntityManagerInterface $em, string $label): VocabularyConcept
    {
        $concept = new VocabularyConcept(VocabularyType::METIER, $label);
        $em->persist($concept);

        return $concept;
    }

    private function exercising(
        EntityManagerInterface $em,
        ?VocabularyConcept $exercised,
        int $monthsInJob,
        int $careerYears,
        ?string $rawTitle = null,
    ): Candidate {
        $person = new Person('Test', 'Bound-'.uniqid(), contactEmail: uniqid('bd-', true).'@example.test', country: 'FR', city: 'Paris');
        $em->persist($person);

        $candidate = new Candidate(
            $person,
            jarviProfileId: Uuid::v7(),
            visibility: ProfileVisibility::VISIBLE,
            syncStatus: SyncStatus::SYNCED,
            syncedAt: new DateTimeImmutable(),
        );
        $candidate->giveConsent(ConsentSource::ONBOARDING, new DateTimeImmutable());
        $em->persist($candidate);

        $jarvi = new CandidateJarvi($candidate);
        $jarvi->experienceYears = $careerYears;
        $em->persist($jarvi);
        $candidate->setJarvi($jarvi);

        if (null !== $exercised) {
            $candidate->getJobExperiences()->add(
                new CandidateJobExperience($candidate, $exercised, months: $monthsInJob, positions: 1, exactPositions: 1),
            );
        }

        if (null !== $rawTitle) {
            $title = new CandidateJobTitle($candidate, $rawTitle);
            $em->persist($title);
        }

        return $candidate;
    }
}
