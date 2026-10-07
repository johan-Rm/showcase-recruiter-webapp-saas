<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\CriterionMatcher;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobExperience;
use App\Entity\Person;
use App\Entity\VocabularyConcept;
use App\Enum\ConsentSource;
use App\Enum\CurrentPositionCertainty;
use App\Enum\JobTenure;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Le métier reconnu est-il ENCORE exercé (task API-026) ?
 *
 * Le critère de poste repose sur un cumul de carrière : il dit qu'une personne a exercé ce métier,
 * jamais quand. Sur le vivier réel, parmi les 293 profils ayant exercé « Développeur logiciel », 90
 * le font encore, 92 sont passés à autre chose et 111 ne disent rien — trois populations que le
 * recruteur trie différemment et que l'écran montrait à l'identique.
 *
 * On n'affirme que ce qu'on sait : le silence couvre les inconnus, et il ne veut pas dire « passé ».
 */
final class CandidateJobTenureTest extends ApiTestCase
{
    public function testAJobStillHeldIsReportedAsCurrent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('cu');
        $concept = $this->concept($em, 'Développeur '.$tag);

        $candidate = $this->exercising($em, $concept, current: $concept, certainty: CurrentPositionCertainty::CERTAIN);
        $em->flush();

        self::assertSame(JobTenure::CURRENT, $this->tenureFor($candidate, 'Développeur '.$tag));
    }

    public function testAJobLeftForAnotherOneIsReportedAsPast(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('pa');
        $concept = $this->concept($em, 'Développeur '.$tag);
        $elsewhere = $this->concept($em, 'Chef de projet '.$tag);

        $candidate = $this->exercising($em, $concept, current: $elsewhere, certainty: CurrentPositionCertainty::CERTAIN);
        $em->flush();

        self::assertSame(JobTenure::PAST, $this->tenureFor($candidate, 'Développeur '.$tag));
    }

    /**
     * Sans poste actuel connu, on se tait — on ne conclut pas au passé.
     *
     * C'est le cas le plus fréquent après le premier : le déduire d'une absence rangerait les 111
     * profils muets avec les 92 qui ont réellement changé de métier.
     */
    public function testAnUnknownCurrentPositionSaysNothing(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('un');
        $concept = $this->concept($em, 'Développeur '.$tag);

        $candidate = $this->exercising($em, $concept, current: null, certainty: CurrentPositionCertainty::ABSENT);
        $em->flush();

        self::assertNull($this->tenureFor($candidate, 'Développeur '.$tag));
    }

    /**
     * Un poste actuel AMBIGU compte comme inconnu.
     *
     * 410 profils du vivier en portent un : plusieurs postes s'y disputent le rôle de poste du
     * moment. Le doute est déjà mesuré à l'ingestion ; l'ignorer afficherait « actuellement » sur des
     * profils dont on sait qu'on n'est pas sûr.
     */
    public function testAnAmbiguousCurrentPositionCountsAsUnknown(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('am');
        $concept = $this->concept($em, 'Développeur '.$tag);

        $candidate = $this->exercising($em, $concept, current: $concept, certainty: CurrentPositionCertainty::AMBIGUOUS);
        $em->flush();

        self::assertNull($this->tenureFor($candidate, 'Développeur '.$tag));
    }

    public function testAnUnsatisfiedCriterionCarriesNoTenure(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('no');
        $concept = $this->concept($em, 'Développeur '.$tag);

        $candidate = $this->exercising($em, $concept, current: $concept, certainty: CurrentPositionCertainty::CERTAIN);
        $em->flush();

        $matches = self::getContainer()->get(CriterionMatcher::class)->match($candidate, [
            ['facet' => 'job', 'value' => 'Cordiste '.$tag, 'group' => 'metier'],
        ]);

        self::assertFalse($matches[0]->matched);
        self::assertNull($matches[0]->matchedWhen, 'un métier qu’on n’exerce pas n’a ni présent ni passé');
    }

    /**
     * Le poste actuel se lit SANS charger le concept (task API-026).
     *
     * `CriterionMatcher` s'exécute sur chaque profil d'une page qui peut en compter deux cents :
     * charger le concept actuel ligne par ligne serait un N+1. On ne compare que des IDENTIFIANTS,
     * qu'un proxy Doctrine porte sans aller les chercher — et c'est cette propriété-là qu'on
     * vérifie, plutôt qu'un comptage de requêtes qui daterait à la première optimisation voisine.
     *
     * Comparer les LIBELLÉS aurait suffi à tout déclencher.
     */
    public function testTheCurrentJobIsComparedWithoutLoadingIt(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('nq');
        $concept = $this->concept($em, 'Développeur '.$tag);
        $elsewhere = $this->concept($em, 'Chef de projet '.$tag);

        $candidate = $this->exercising($em, $concept, current: $elsewhere, certainty: CurrentPositionCertainty::CERTAIN);
        $em->flush();
        $id = $candidate->getId();
        $em->clear();

        $reloaded = $em->find(Candidate::class, $id);
        self::assertInstanceOf(Candidate::class, $reloaded);

        $matches = self::getContainer()->get(CriterionMatcher::class)->match($reloaded, [
            ['facet' => 'job', 'value' => 'Développeur '.$tag, 'group' => 'metier'],
        ]);

        self::assertSame(JobTenure::PAST, $matches[0]->matchedWhen);

        $current = $reloaded->getJarvi()?->currentJobConcept;
        self::assertInstanceOf(VocabularyConcept::class, $current);
        self::assertTrue(
            $em->getUnitOfWork()->isUninitializedObject($current),
            'le concept du poste actuel ne doit jamais avoir été chargé',
        );
    }

    private function tenureFor(Candidate $candidate, string $job): ?JobTenure
    {
        $matches = self::getContainer()->get(CriterionMatcher::class)->match($candidate, [
            ['facet' => 'job', 'value' => $job, 'group' => 'metier'],
        ]);

        self::assertTrue($matches[0]->matched, 'le critère doit d’abord être satisfait');

        return $matches[0]->matchedWhen;
    }

    private function concept(EntityManagerInterface $em, string $label): VocabularyConcept
    {
        $concept = new VocabularyConcept(VocabularyType::METIER, $label);
        $em->persist($concept);

        return $concept;
    }

    private function exercising(
        EntityManagerInterface $em,
        VocabularyConcept $exercised,
        ?VocabularyConcept $current,
        CurrentPositionCertainty $certainty,
    ): Candidate {
        $person = new Person('Test', 'Tenure-'.uniqid(), contactEmail: uniqid('tn-', true).'@example.test', country: 'FR', city: 'Paris');
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
        $jarvi->currentJobConcept = $current;
        $jarvi->currentPositionCertainty = $certainty;
        $em->persist($jarvi);
        $candidate->setJarvi($jarvi);

        $candidate->getJobExperiences()->add(
            new CandidateJobExperience($candidate, $exercised, months: 24, positions: 1, exactPositions: 1),
        );

        return $candidate;
    }
}
