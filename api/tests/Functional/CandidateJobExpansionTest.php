<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\CriterionMatcher;
use App\Entity\Candidate;
use App\Entity\CandidateJobExperience;
use App\Entity\Person;
use App\Entity\VocabularyConcept;
use App\Entity\VocabularyLabel;
use App\Enum\ConsentSource;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Expansion de la recherche par le vocabulaire (task 125, volet A de 123).
 *
 * Le terme cherché résout vers un concept via son libellé canonique OU un libellé vivant : chercher
 * un synonyme retrouve le concept. Un libellé déprécié « mot à mot » (task 124) ne résout plus.
 */
final class CandidateJobExpansionTest extends ApiTestCase
{
    public function testSynonymFindsTheConceptAndDeprecatedLabelDoesNot(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $tag = uniqid();
        $concept = new VocabularyConcept(VocabularyType::METIER, 'Développeur test-'.$tag);
        $em->persist($concept);
        $em->persist(new VocabularyLabel($concept, 'software engineer test-'.$tag, 'en'));
        // Synonyme parasite exclu par un admin (task 124) : il ne doit plus élargir la recherche.
        $em->persist(new VocabularyLabel($concept, 'stagiaire parasite-'.$tag, 'fr', status: VocabularyStatus::DEPRECIE));

        $candidate = $this->persistSearchable($em);
        $candidate->getJobExperiences()->add(new CandidateJobExperience($candidate, $concept, months: 24, positions: 2, exactPositions: 1));
        $em->flush();

        $id = $candidate->getId();

        // Un synonyme VIVANT retrouve le concept — c'est l'expansion promise par l'UI.
        self::assertContains($id, $this->ids($repository, 'software engineer test-'.$tag), 'un synonyme vivant élargit vers le concept');
        // Insensible à la casse (la combobox peut renvoyer n'importe quelle graphie).
        self::assertContains($id, $this->ids($repository, 'SOFTWARE ENGINEER TEST-'.$tag), 'résolution insensible à la casse');
        // Le libellé canonique fonctionne aussi.
        self::assertContains($id, $this->ids($repository, 'Développeur test-'.$tag), 'le libellé canonique résout');
        // Un libellé DÉPRÉCIÉ ne résout plus : chercher ce terme n'élargit pas.
        self::assertNotContains($id, $this->ids($repository, 'stagiaire parasite-'.$tag), 'un libellé déprécié n’élargit plus');
        // Un terme qui n'est ni prefLabel ni libellé ne trouve rien.
        self::assertNotContains($id, $this->ids($repository, 'terme-inconnu-'.$tag), 'un terme inconnu ne résout pas');
    }

    /**
     * L'explication de correspondance rapporte le libellé DU PROFIL, pas le terme cherché.
     *
     * C'est tout l'intérêt : le recruteur a tapé « software engineer », la fiche affiche
     * « Développeur » — la chaîne cherchée n'est nulle part dans le profil, il n'y a donc rien à
     * surligner. Seul le libellé du concept permet d'énoncer la correspondance, et de rendre visible
     * le travail du dictionnaire.
     */
    public function testMatchReportsTheProfileLabelAndNotTheSearchedTerm(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $matcher = self::getContainer()->get(CriterionMatcher::class);

        $tag = uniqid();
        $concept = new VocabularyConcept(VocabularyType::METIER, 'Développeur test-'.$tag);
        $em->persist($concept);
        $em->persist(new VocabularyLabel($concept, 'software engineer test-'.$tag, 'en'));

        $candidate = $this->persistSearchable($em);
        $candidate->getJobExperiences()->add(new CandidateJobExperience($candidate, $concept, months: 24, positions: 2, exactPositions: 1));
        $em->flush();

        $matches = $matcher->match($candidate, [
            ['facet' => 'job', 'value' => 'software engineer test-'.$tag, 'group' => 'metier'],
            ['facet' => 'job', 'value' => 'terme-inconnu-'.$tag, 'group' => 'metier'],
        ]);

        self::assertCount(2, $matches);

        self::assertTrue($matches[0]->matched);
        self::assertSame('Développeur test-'.$tag, $matches[0]->matchedOn, 'le libellé du PROFIL, pas celui cherché');
        self::assertSame('software engineer test-'.$tag, $matches[0]->value, 'le critère est rendu tel qu’il a été posé');

        self::assertFalse($matches[1]->matched);
        self::assertNull($matches[1]->matchedOn, 'un critère non satisfait ne rapporte rien du profil');
    }

    /** @return list<int> */
    private function ids(CandidateRepository $repository, string $job): array
    {
        return $repository->matchingIds(new CandidateSearchCriteria(job: $job));
    }

    private function persistSearchable(EntityManagerInterface $em): Candidate
    {
        $person = new Person('Test', 'Expansion-'.uniqid(), contactEmail: uniqid('exp-', true).'@example.test', country: 'FR', city: 'Paris');
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

        return $candidate;
    }
}
