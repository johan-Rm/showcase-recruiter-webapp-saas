<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\CriterionMatcher;
use App\Domain\Search\SuggestionFinder;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobExperience;
use App\Entity\CandidateJobTitle;
use App\Entity\Person;
use App\Entity\VocabularyConcept;
use App\Entity\VocabularyLabel;
use App\Enum\ConsentSource;
use App\Enum\ProfileVisibility;
use App\Enum\SuggestionKind;
use App\Enum\SyncStatus;
use App\Enum\VocabularyType;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Le filet sous le dictionnaire — task API-037.
 *
 * Un intitulé que le dictionnaire ignore ne produisait aucune ligne cherchable : le profil qui le
 * portait était hors d'atteinte, définitivement. Ce que ces tests protègent n'est pas seulement que
 * le filet trouve, c'est qu'il ne se déclenche **que** lorsque le dictionnaire est muet — un repli
 * qui s'ajouterait au cas nominal changerait tous les décomptes annoncés au recruteur.
 */
final class CandidateJobTitleFallbackTest extends ApiTestCase
{
    public function testAnUnknownTitleFindsTheProfilesThatWroteIt(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $tag = uniqid();

        $candidate = $this->persistSearchable($em);
        $candidate->getJobTitles()->add(new CandidateJobTitle($candidate, 'zzzarchitecte du vide '.$tag));
        $em->flush();

        $id = $candidate->getId();
        self::assertContains($id, $this->ids($repository, 'zzzarchitecte du vide '.$tag), 'l’intitulé écrit est trouvé');
        // Sous-chaîne : le recruteur n'a pas à retrouver l'intitulé complet mot pour mot.
        self::assertContains($id, $this->ids($repository, 'architecte du vide '.$tag), 'une partie de l’intitulé suffit');
        self::assertContains($id, $this->ids($repository, 'ZZZARCHITECTE DU VIDE '.$tag), 'insensible à la casse');
    }

    /**
     * LE TEST QUI PORTE LA TÂCHE : le filet ne s'ajoute jamais au dictionnaire.
     *
     * Un terme que le dictionnaire connaît doit ramener exactement ce qu'il ramenait avant — sinon
     * tous les volumes annoncés au recruteur changent, et pour une raison qu'aucun écran n'explique.
     */
    public function testAKnownTermIgnoresTheWrittenTitlesEntirely(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $tag = uniqid();
        $term = 'zzzsoudeur '.$tag;

        // Le concept existe et quelqu'un l'exerce : le terme est donc « connu ».
        $concept = new VocabularyConcept(VocabularyType::METIER, 'Zzzsoudeur '.$tag);
        $em->persist($concept);
        $em->persist(new VocabularyLabel($concept, $term, 'fr'));

        $exercising = $this->persistSearchable($em);
        $exercising->getJobExperiences()->add(new CandidateJobExperience($exercising, $concept, months: 12, positions: 1, exactPositions: 1));

        // Celui-ci a écrit le terme dans son parcours, mais n'exerce pas le concept.
        $writing = $this->persistSearchable($em);
        $writing->getJobTitles()->add(new CandidateJobTitle($writing, 'apprenti '.$term));
        $em->flush();

        $found = $this->ids($repository, $term);
        self::assertContains($exercising->getId(), $found, 'le concept exercé répond');
        self::assertNotContains($writing->getId(), $found, 'le filet ne s’ajoute pas quand le dictionnaire répond');
    }

    /** Un terme trop court chercherait une sous-chaîne dans tout le vivier : il ne cherche rien. */
    public function testATooShortTermDoesNotTriggerTheFallback(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $candidate = $this->persistSearchable($em);
        $candidate->getJobTitles()->add(new CandidateJobTitle($candidate, 'zzz développeur du vide'));
        $em->flush();

        self::assertNotContains($candidate->getId(), $this->ids($repository, 'zzz'), 'trois lettres ne déclenchent pas le filet');
    }

    /** Un profil retenu par le filet dit par quoi : l'intitulé qu'il a écrit, pas le terme cherché. */
    public function testTheMatchReportsTheWrittenTitle(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $matcher = self::getContainer()->get(CriterionMatcher::class);
        $tag = uniqid();

        $candidate = $this->persistSearchable($em);
        $candidate->getJobTitles()->add(new CandidateJobTitle($candidate, 'zzzarchitecte du vide '.$tag.' — chargé de projet'));
        $em->flush();

        $matches = $matcher->match($candidate, [
            ['facet' => 'job', 'value' => 'zzzarchitecte du vide '.$tag, 'group' => 'metier'],
        ]);

        self::assertTrue($matches[0]->matched);
        self::assertSame(
            'zzzarchitecte du vide '.$tag.' — chargé de projet',
            $matches[0]->matchedOn,
            'ce que la personne a écrit, qui est plus long que ce qui a été tapé',
        );
    }

    /**
     * L'ordre des trois passages de l'auto-complétion, qui est le cœur de l'arbitrage.
     *
     * Le rattrapage aux fautes de frappe compare le terme au meilleur EXTRAIT d'un libellé : sans
     * départage, « zzzarchitecte du vide » ressemblerait assez à « Zzzarchitecte » pour remonter le
     * concept, et le filet ne servirait jamais. Le nombre de mots tranche — une faute déforme un mot,
     * une précision en ajoute.
     */
    public function testWrittenTitlesComeBeforeTheTypoRescueOnlyForMultiWordTerms(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $finder = self::getContainer()->get(SuggestionFinder::class);
        $tag = mb_strtolower(uniqid());

        $concept = new VocabularyConcept(VocabularyType::METIER, 'Zzzchaudronnier '.$tag);
        $em->persist($concept);
        $em->persist(new VocabularyLabel($concept, 'zzzchaudronnier '.$tag, 'fr'));

        $exercising = $this->persistSearchable($em);
        $exercising->getJobExperiences()->add(new CandidateJobExperience($exercising, $concept, months: 12, positions: 1, exactPositions: 1));

        $writing = $this->persistSearchable($em);
        $writing->getJobTitles()->add(new CandidateJobTitle($writing, 'zzzchaudronnier '.$tag.' naval'));
        $em->flush();

        // Un mot, correspondance exacte : le concept, comme toujours.
        $exact = $finder->jobs('zzzchaudronnier '.$tag, 10);
        self::assertSame(SuggestionKind::CONCEPT, $exact['kind']);
        self::assertContains('Zzzchaudronnier '.$tag, $exact['values']);

        // Plusieurs mots, aucune correspondance exacte : l'intitulé écrit passe devant le rattrapage.
        $written = $finder->jobs('zzzchaudronnier '.$tag.' naval', 10);
        self::assertSame(SuggestionKind::LITERAL, $written['kind'], 'un terme plus précis n’est pas une faute de frappe');
        self::assertSame(['zzzchaudronnier '.$tag.' naval'], $written['values']);
    }

    /** @return list<int> */
    private function ids(CandidateRepository $repository, string $job): array
    {
        return $repository->matchingIds(new CandidateSearchCriteria(job: $job));
    }

    private function persistSearchable(EntityManagerInterface $em): Candidate
    {
        $person = new Person('Test', 'Fallback-'.uniqid(), contactEmail: uniqid('fb-', true).'@example.test', country: 'FR', city: 'Paris');
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
        $em->persist(new CandidateJarvi($candidate));

        return $candidate;
    }
}
