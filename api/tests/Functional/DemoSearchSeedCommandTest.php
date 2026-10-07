<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\Demo\DemoSearchCatalog;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobExperience;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\VocabularyConcept;
use App\Enum\ProfileVisibility;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * L'amorçage du cockpit de démonstration — task API-029.
 *
 * Ce qui doit être prouvé n'est pas qu'une commande écrit des lignes, mais qu'elle produit une
 * GRILLE UTILISABLE : tous les états de carte représentés, des compteurs qui disent la vérité sur le
 * jeu de résultats, et aucune casse du travail déjà en place — le vivier n'est pas purgé, et les
 * recherches d'essai d'un développeur ne sont pas récupérées au passage.
 */
final class DemoSearchSeedCommandTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';

    /**
     * Le métier que le test rattache à un candidat pour obtenir un vrai jeu de résultats.
     *
     * Il désigne une recherche ACTIVE du catalogue, et pas une close : sur une recherche close,
     * l'API éteint les nouveautés (task API-028), et le test comparerait un zéro légitime à ce que
     * le catalogue prévoit.
     */
    private const string SEEDED_JOB = 'Juriste';

    public function testTheGridIsCreatedWithEveryCardState(): void
    {
        $this->seed();

        $searches = $this->ownedSearches();
        self::assertGreaterThanOrEqual(50, \count($searches), 'La grille doit compter au moins cinquante recherches.');

        self::assertNotEmpty(array_filter($searches, static fn (SavedSearch $s): bool => $s->closedAt instanceof DateTimeImmutable), 'Aucune recherche close.');
        self::assertNotEmpty(array_filter($searches, static fn (SavedSearch $s): bool => $s->isShared), 'Aucune recherche partagée.');
        self::assertEmpty(array_filter($searches, static fn (SavedSearch $s): bool => [] === $s->criteria), 'Une recherche sans critère ne ramène rien : la grille n\'en veut pas.');
        self::assertNotEmpty($this->colleagueSearches(), 'Aucune recherche portée par le collègue : la carte en lecture seule est irreprésentable.');
    }

    /** Rejouable : c'est ce qui la rend utilisable après un import ou une reprise de base. */
    public function testRunningItTwiceAddsNothing(): void
    {
        $this->seed();
        $first = \count($this->ownedSearches()) + \count($this->colleagueSearches());

        $display = $this->seed()->getDisplay();

        self::assertSame($first, \count($this->ownedSearches()) + \count($this->colleagueSearches()));
        self::assertStringContainsString('0 recherche(s) créée(s)', $display);
    }

    /**
     * Les recherches d'essai d'un développeur ne portent qu'un critère ; le catalogue en pose au
     * moins deux. Une carte du catalogue ne peut donc pas se poser sur un essai — ni le renommer, ni
     * le fermer, ni lui coller des favoris.
     */
    public function testAnExistingOneCriterionSearchIsLeftAlone(): void
    {
        $em = $this->em();
        $owner = $this->recruiter(self::OWNER);
        $trial = new SavedSearch($owner, $owner->organization, '', [
            ['facet' => 'job', 'value' => self::SEEDED_JOB, 'group' => 'metier'],
        ]);
        $em->persist($trial);
        $em->flush();
        $trialId = $trial->getId();

        $this->seed();

        $em->clear();
        $reloaded = $em->getRepository(SavedSearch::class)->find($trialId);
        self::assertInstanceOf(SavedSearch::class, $reloaded);
        self::assertSame('', $reloaded->name);
        self::assertNull($reloaded->closedAt);
        self::assertFalse($reloaded->isShared);
        self::assertCount(1, $reloaded->criteria, 'Les critères de l\'essai ont été réécrits.');
    }

    /**
     * Les quatre nombres de la carte, sur un jeu de résultats RÉEL : le test rattache un candidat du
     * vivier au métier d'une entrée du catalogue, puis confronte ce que l'API annonce à ce que le
     * catalogue prévoit pour ce rang.
     *
     * C'est le cœur de la tâche : une carte qui annoncerait autre chose que ce que la Table sert
     * ferait chercher un bug inexistant.
     */
    public function testTheCardAnnouncesWhatTheCatalogueIntends(): void
    {
        $this->attachSeededJobToACandidate();
        $this->seed();

        $card = $this->cardFor(self::SEEDED_JOB);
        self::assertSame(1, $card['resultCount'], 'Le candidat rattaché doit constituer le jeu.');

        $expected = (new DemoSearchCatalog())
            ->progressAt($this->seededDefinition()['index'])
            ->sharesFor($card['resultCount']);

        self::assertSame($expected['seen'], $card['seenCount']);
        self::assertSame($expected['retained'], $card['favoriteCount']);
        self::assertSame($expected['fresh'], $card['newCount']);
    }

    /** @param array<string, string> $options */
    private function seed(array $options = []): CommandTester
    {
        $application = new Application(self::$kernel ?? self::bootKernel());
        $tester = new CommandTester($application->find('app:searches:seed-demo'));
        $tester->execute(['--recruiter' => self::OWNER] + $options);
        $tester->assertCommandIsSuccessful();

        return $tester;
    }

    /**
     * La carte servie par l'API pour la recherche du catalogue portant ce métier — celle que le
     * recruteur verra, compteurs compris.
     *
     * @return array<string, mixed>
     */
    private function cardFor(string $job): array
    {
        $token = $this->loginToken(self::OWNER);
        $this->request('GET', '/v1/searches', $token);
        self::assertResponseIsSuccessful();

        /** @var list<array<string, mixed>> $items */
        $items = $this->jsonResponse()['items'];
        foreach ($items as $item) {
            /** @var list<array{facet: string, value: string, group: string}> $criteria */
            $criteria = $item['criteria'];
            if ($criteria === $this->seededDefinition()['criteria']) {
                return $item;
            }
        }

        self::fail(\sprintf('Aucune carte du catalogue pour le métier « %s ».', $job));
    }

    /**
     * L'entrée du catalogue portant le métier rattaché, avec son RANG : c'est le rang qui détermine
     * l'avancement, le lire ici évite de figer dans le test un ordre qui peut bouger.
     *
     * @return array{index: int, criteria: list<array{facet: string, value: string, group: string}>}
     */
    private function seededDefinition(): array
    {
        foreach ((new DemoSearchCatalog())->definitions() as $index => $definition) {
            foreach ($definition->criteria as $criterion) {
                if ('job' === $criterion['facet'] && self::SEEDED_JOB === $criterion['value']) {
                    return ['index' => $index, 'criteria' => $definition->criteria];
                }
            }
        }

        self::fail(\sprintf('Le catalogue ne porte plus de recherche « %s ».', self::SEEDED_JOB));
    }

    /**
     * Fabrique un candidat qui satisfait TOUS les critères de l'entrée visée : son métier exercé, et
     * l'une des compétences qu'elle demande.
     *
     * Les fixtures ne portent ni métier exercé ni compétence du catalogue — sans ce rattachement,
     * toutes les recherches ramèneraient zéro profil et aucun compteur ne serait vérifiable. La
     * compétence est lue sur la définition plutôt qu'écrite en dur : c'est ce qui a rattrapé
     * l'enrichissement des mots-clés, qui avait vidé ce jeu sans que le catalogue soit fautif.
     */
    private function attachSeededJobToACandidate(): void
    {
        $em = $this->em();
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['visibility' => ProfileVisibility::VISIBLE]);
        self::assertInstanceOf(Candidate::class, $candidate);
        $candidate->getPerson()->country = 'FR';

        $concept = new VocabularyConcept(
            VocabularyType::METIER,
            self::SEEDED_JOB,
            code: 'metier-test-juriste',
            status: VocabularyStatus::VALIDE,
        );
        $em->persist($concept);
        $em->persist(new CandidateJobExperience($candidate, $concept, months: 24, positions: 1, exactPositions: 1));

        $jarvi = $em->getRepository(CandidateJarvi::class)->findOneBy(['candidate' => $candidate]);
        self::assertInstanceOf(CandidateJarvi::class, $jarvi);
        $jarvi->skills = [...$jarvi->skills, ...$this->seededKeywords()];

        $em->flush();
    }

    /**
     * Les mots-clés que l'entrée visée demande. Ils sont reliés en OU : en porter un suffit.
     *
     * @return list<string>
     */
    private function seededKeywords(): array
    {
        $keywords = array_values(array_filter(
            $this->seededDefinition()['criteria'],
            static fn (array $criterion): bool => 'q' === $criterion['facet'],
        ));

        return array_map(static fn (array $criterion): string => $criterion['value'], $keywords);
    }

    /** @return list<SavedSearch> */
    private function ownedSearches(): array
    {
        return $this->em()->getRepository(SavedSearch::class)->findBy(['owner' => $this->recruiter(self::OWNER)]);
    }

    /** @return list<SavedSearch> */
    private function colleagueSearches(): array
    {
        return $this->em()->getRepository(SavedSearch::class)
            ->findBy(['owner' => $this->recruiter('atlas.member@demo.showcase-recruiter-webapp-saas.example')]);
    }

    private function recruiter(string $email): Recruiter
    {
        $recruiter = $this->em()->createQueryBuilder()
            ->select('r')
            ->from(Recruiter::class, 'r')
            ->join('r.person', 'p')
            ->where('p.contactEmail = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(Recruiter::class, $recruiter);

        return $recruiter;
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
