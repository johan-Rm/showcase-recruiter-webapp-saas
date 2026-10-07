<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\CriterionMatcher;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobTitle;
use App\Entity\Person;
use App\Enum\ConsentSource;
use App\Enum\MatchField;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Uid\Uuid;

/**
 * Un mot-clé cherche dans trois endroits du profil, et dit lequel a répondu (task API-040).
 *
 * DEUX CHOSES SE TESTENT ICI, et la seconde est la plus importante. Le filtre doit ramener les
 * profils qui portent le terme dans l'un des trois endroits — accroche, compétences, postes écrits.
 * Mais surtout, TOUT PROFIL RAMENÉ PAR LE FILTRE DOIT VOIR SON MOT-CLÉ ANNONCÉ COMME
 * SATISFAIT : le filtre restreint en SQL, l'explication se recalcule en PHP, et ces deux lectures de
 * la même règle finissent par diverger. Le symptôme serait un profil rendu par la recherche dont la
 * colonne du verdict affirme qu'il ne correspond pas.
 */
final class SearchKeywordFieldsTest extends ApiTestCase
{
    /** Un terme qu'aucun vrai profil ne porte : le vivier de test n'est pas garanti vide. */
    private const string TERME = 'zzkeywordfieldszz';

    /** Un second terme, pour prouver que deux mots-clés cohabitent dans la même requête. */
    private const string AUTRE_TERME = 'zzautretermezz';

    public function testChaqueEndroitDuProfilEstCherche(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $parAccroche = $this->persistCandidate($em, headline: 'Expert '.self::TERME.' senior');
        $parCompetence = $this->persistCandidate($em, skills: ['php', self::TERME]);
        $parPoste = $this->persistCandidate($em, jobTitles: ['Ingénieur '.self::TERME]);
        $sansRien = $this->persistCandidate($em, headline: 'Rien à voir', skills: ['php']);
        $em->flush();

        $ids = $repository->matchingIds(new CandidateSearchCriteria(keywords: [self::TERME]));

        self::assertContains((int) $parAccroche->getId(), $ids, 'accroche');
        self::assertContains((int) $parCompetence->getId(), $ids, 'compétence');
        self::assertContains((int) $parPoste->getId(), $ids, 'intitulé de poste écrit');
        self::assertNotContains((int) $sansRien->getId(), $ids, 'un profil sans le terme ne remonte pas');
    }

    public function testPlusieursMotsClesSeCherchentDansLaMemeRequete(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $premier = $this->persistCandidate($em, jobTitles: ['Chef '.self::TERME]);
        $second = $this->persistCandidate($em, jobTitles: ['Adjoint '.self::AUTRE_TERME]);
        $em->flush();

        // DEUX mots-clés, donc deux sous-requêtes dans la MÊME requête : un alias écrit en dur les
        // ferait entrer en collision et Doctrine refuserait le tout. Un seul mot-clé ne le voit pas.
        $ids = $repository->matchingIds(
            new CandidateSearchCriteria(keywords: [self::TERME, self::AUTRE_TERME]),
        );

        self::assertContains((int) $premier->getId(), $ids);
        self::assertContains((int) $second->getId(), $ids);
    }

    public function testToutProfilRamenéSExpliqueEtDitOu(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);
        $matcher = self::getContainer()->get(CriterionMatcher::class);

        $attendu = [
            MatchField::HEADLINE->value => $this->persistCandidate($em, headline: 'Expert '.self::TERME),
            MatchField::SKILL->value => $this->persistCandidate($em, skills: [self::TERME]),
            MatchField::JOB_TITLE->value => $this->persistCandidate($em, jobTitles: ['Chef '.self::TERME]),
        ];
        $em->flush();

        // La réplica et les postes viennent d'être écrits par leur côté propriétaire : sans
        // rechargement, le profil en mémoire ne les porte pas encore, et l'explication lirait le vide.
        foreach ($attendu as $candidate) {
            $em->refresh($candidate);
        }

        $criteres = [['facet' => 'q', 'value' => self::TERME, 'group' => 'keywords']];
        $ids = $repository->matchingIds(new CandidateSearchCriteria(keywords: [self::TERME]));

        foreach ($attendu as $champ => $candidate) {
            self::assertContains((int) $candidate->getId(), $ids, 'le filtre ramène le profil de '.$champ);

            $matches = $matcher->match($candidate, $criteres);
            self::assertCount(1, $matches);
            // L'INVARIANT : ramené par le filtre, donc expliqué comme satisfait.
            self::assertTrue($matches[0]->matched, 'le profil de '.$champ.' est ramené sans être expliqué');
            self::assertSame($champ, $matches[0]->matchedIn?->value, 'la provenance annoncée');
            self::assertNotNull($matches[0]->matchedOn, 'l extrait du profil');
        }
    }

    public function testLesPostesSontChargesEnUneFoisPourLaPage(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $matcher = self::getContainer()->get(CriterionMatcher::class);

        $page = [
            $this->persistCandidate($em, jobTitles: ['Chef '.self::TERME]),
            $this->persistCandidate($em, jobTitles: ['Chef adjoint '.self::TERME]),
        ];
        $em->flush();
        $em->clear();

        // Rechargés comme le ferait la requête de page : la collection des postes n'est pas encore
        // lue, et la lire profil par profil serait le N+1 que `warmUp` existe pour éviter.
        $page = array_map(
            static fn (Candidate $c): Candidate => $em->find(Candidate::class, $c->getId()),
            $page,
        );

        // `isInitialized()` n'appartient pas à l'interface d'une collection mais à sa version
        // paresseuse : c'est précisément celle qu'on veut voir ici, et l'assertion de type le dit.
        foreach ($page as $candidate) {
            $titres = $candidate->getJobTitles();
            self::assertInstanceOf(PersistentCollection::class, $titres);
            self::assertFalse($titres->isInitialized(), 'avant : rien n est chargé');
        }

        $matcher->warmUp($page);

        foreach ($page as $candidate) {
            $titres = $candidate->getJobTitles();
            self::assertInstanceOf(PersistentCollection::class, $titres);
            self::assertTrue(
                $titres->isInitialized(),
                'après : les postes sont là, sans que personne ait eu à les demander',
            );
        }
    }

    public function testUnNomDeRubriqueNeRamenePasToutLeVivier(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        // Un parcours complet, avec ses rubriques : c'est leur SÉRIALISATION qui piégerait une
        // recherche lisant la structure brute — chaque profil porte le mot « companyName ».
        $this->persistCandidate($em, workExperiences: [
            ['title' => 'Développeur', 'companyName' => 'Atlas', 'description' => 'Une description'],
        ]);
        $em->flush();

        foreach (['companyName', 'description', 'startDate'] as $rubrique) {
            self::assertSame(
                [],
                $repository->matchingIds(new CandidateSearchCriteria(keywords: [$rubrique])),
                'le nom de rubrique « '.$rubrique.' » ne doit ramener personne',
            );
        }
    }

    /**
     * @param list<string>               $skills
     * @param list<string>               $jobTitles
     * @param list<array<string, mixed>> $workExperiences
     */
    private function persistCandidate(
        EntityManagerInterface $em,
        ?string $headline = null,
        array $skills = [],
        array $jobTitles = [],
        array $workExperiences = [],
    ): Candidate {
        $person = new Person('Test', 'Mots-'.uniqid(), contactEmail: uniqid('kw-', true).'@example.test', country: 'FR', city: 'Paris');
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
        $jarvi->headline = $headline;
        $jarvi->skills = $skills;
        $jarvi->workExperiences = $workExperiences;
        $em->persist($jarvi);

        foreach ($jobTitles as $title) {
            $em->persist(new CandidateJobTitle($candidate, $title));
        }

        return $candidate;
    }
}
