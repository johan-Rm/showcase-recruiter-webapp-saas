<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobExperience;
use App\Entity\Person;
use App\Entity\VocabularyConcept;
use App\Entity\VocabularyLabel;
use App\Enum\ConsentSource;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Auto-complétion des champs de recherche (task API-009).
 *
 * Deux sources : le dictionnaire de concepts pour l'intitulé de poste, les compétences réellement
 * portées par les profils pour les mots-clés. Chaque proposition annonce ce qu'elle ramènerait.
 */
final class SuggestionTest extends ApiTestCase
{
    private const string OWNER = 'owner@demo.showcase-recruiter-webapp-saas.example';

    public function testSkillSuggestionsComeFromThePoolAndCarryTheirCount(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('sk'));
        $this->persistProfile($em, ['Kubernetes '.$tag]);
        $this->persistProfile($em, ['Kubernetes '.$tag, 'Kotlin '.$tag]);
        $em->flush();

        $this->request('GET', '/v1/suggestions?type=SKILL&q=k'.mb_substr($tag, 2), $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        $items = $this->jsonResponse()['items'];
        // Rien ne remonte : le préfixe cherché n'est le début d'aucune compétence.
        self::assertSame([], $items);

        $this->request('GET', '/v1/suggestions?type=SKILL&q=kubernetes '.$tag, $this->loginToken(self::OWNER));
        $items = $this->jsonResponse()['items'];
        self::assertCount(1, $items);
        self::assertSame('kubernetes '.$tag, $items[0]['value']);
        self::assertArrayNotHasKey('profileCount', $items[0], 'le nombre de profils ne sort plus du serveur');
    }

    public function testJobSuggestionsResolveThroughTheDictionaryAndNeverReturnZero(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('jb'));

        $exercised = new VocabularyConcept(VocabularyType::METIER, 'Développeur '.$tag);
        $em->persist($exercised);
        // Un synonyme anglais : chercher par lui doit proposer le libellé CANONIQUE du concept.
        $em->persist(new VocabularyLabel($exercised, 'software engineer '.$tag, 'en'));

        // Un concept que PERSONNE n'exerce : il ne doit jamais être proposé.
        $em->persist(new VocabularyConcept(VocabularyType::METIER, 'Développeur fantôme '.$tag));

        $candidate = $this->persistProfile($em, []);
        $candidate->getJobExperiences()->add(new CandidateJobExperience($candidate, $exercised, months: 24, positions: 1, exactPositions: 1));
        $em->flush();

        $token = $this->loginToken(self::OWNER);

        $this->request('GET', '/v1/suggestions?type=JOB&q=software engineer '.$tag, $token);
        self::assertResponseIsSuccessful();
        $items = $this->jsonResponse()['items'];
        self::assertCount(1, $items);
        self::assertSame('Développeur '.$tag, $items[0]['value'], 'le libellé canonique, pas le synonyme tapé');
        self::assertArrayNotHasKey('profileCount', $items[0]);

        // « Développeur » préfixe les deux concepts, mais seul l'exercé est proposé.
        $this->request('GET', '/v1/suggestions?type=JOB&q=développeur '.$tag, $token);
        $values = array_column($this->jsonResponse()['items'], 'value');
        self::assertSame(['Développeur '.$tag], $values, 'un concept que personne n’exerce ne se propose pas');
    }

    /**
     * « dev » propose « Développeur » (task API-016).
     *
     * Personne ne tape son accent dans un champ de recherche : sans cette normalisation, le champ
     * reste muet sur la plupart des intitulés français, et le recruteur en conclut que le vivier ne
     * contient personne.
     *
     * L'extension est posée ICI plutôt que supposée : elle ne fait pas partie du schéma Doctrine, et
     * la base de test ne la reçoit donc pas. La transaction du test l'emporte avec elle — les autres
     * tests exercent du même coup le repli accentué.
     */
    public function testAccentsAreIgnoredOnBothSidesOfTheComparison(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS unaccent');

        $tag = mb_strtolower(uniqid('ac'));
        $concept = new VocabularyConcept(VocabularyType::METIER, 'Développeur '.$tag);
        $em->persist($concept);

        $candidate = $this->persistProfile($em, []);
        $candidate->getJobExperiences()->add(new CandidateJobExperience($candidate, $concept, months: 24, positions: 1, exactPositions: 1));
        $em->flush();

        $token = $this->loginToken(self::OWNER);

        // Sans accent : c'est le cas qui ne marchait pas.
        $this->request('GET', '/v1/suggestions?type=JOB&q=developpeur '.$tag, $token);
        self::assertResponseIsSuccessful();
        self::assertSame(['Développeur '.$tag], array_column($this->jsonResponse()['items'], 'value'));

        // Avec accent : la normalisation des deux côtés ne doit rien casser de ce qui marchait.
        $this->request('GET', '/v1/suggestions?type=JOB&q=développeur '.$tag, $token);
        self::assertSame(['Développeur '.$tag], array_column($this->jsonResponse()['items'], 'value'));
    }

    /**
     * « logiciel » retrouve « Développeur logiciel » (task API-023).
     *
     * Le préfixe seul obligeait à commencer par le premier mot du libellé, ce que rien à l'écran ne
     * disait. Le début de N'IMPORTE QUEL mot le corrige — sans devenir une sous-chaîne : le second
     * appel vérifie qu'un fragment pris au MILIEU d'un mot ne retrouve toujours rien.
     */
    public function testATermIsFoundAtTheStartOfAnyWordButNotInsideOne(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('wd'));

        $concept = new VocabularyConcept(VocabularyType::METIER, 'Ingénieur '.$tag);
        $em->persist($concept);
        $this->exercise($em, $concept);
        $em->flush();

        $token = $this->loginToken(self::OWNER);

        $this->request('GET', '/v1/suggestions?type=JOB&q='.$tag, $token);
        self::assertResponseIsSuccessful();
        self::assertSame(['Ingénieur '.$tag], array_column($this->jsonResponse()['items'], 'value'));

        // Le même terme amputé de sa première lettre tombe au milieu du mot : rien ne doit sortir.
        $this->request('GET', '/v1/suggestions?type=JOB&q='.mb_substr($tag, 1), $token);
        self::assertSame([], $this->jsonResponse()['items'], 'une sous-chaîne n’est pas un début de mot');
    }

    /**
     * Ce qui COMMENCE par le terme passe devant ce qui le contient (task API-023).
     *
     * Sans ce départage, chercher « dev » remontait « Ingénieur d'études » — 493 profils, dont une
     * variante contient « développement » — devant « Développeur logiciel », qui était le premier
     * résultat avant la tâche. Le volume ne doit pas racheter une correspondance plus lointaine.
     */
    public function testWhatBeginsWithTheTermComesBeforeWhatMerelyContainsIt(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('or'));

        $begins = new VocabularyConcept(VocabularyType::METIER, $tag.' embarqué');
        $contains = new VocabularyConcept(VocabularyType::METIER, 'Ingénieur '.$tag);
        $em->persist($begins);
        $em->persist($contains);

        // Celui qui ne fait que CONTENIR le terme est le plus porté : c'est tout l'intérêt du cas.
        $this->exercise($em, $begins);
        $this->exercise($em, $contains);
        $this->exercise($em, $contains);
        $em->flush();

        $this->request('GET', '/v1/suggestions?type=JOB&q='.$tag, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();
        self::assertSame(
            [$tag.' embarqué', 'Ingénieur '.$tag],
            array_column($this->jsonResponse()['items'], 'value'),
        );
    }

    /**
     * Une faute de frappe est rattrapée, mais seulement à défaut de mieux (task API-023).
     *
     * Le secours ne se déclenche QUE si le passage exact n'a rien ramené : c'est ce qui permet un
     * seuil généreux sans qu'aucun bruit ne vienne polluer une recherche qui marchait.
     *
     * L'extension est posée ICI plutôt que supposée — elle ne fait pas partie du schéma Doctrine, et
     * la transaction du test l'emporte avec elle.
     */
    public function testATypoIsRescuedOnlyWhenNothingExactWasFound(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $tag = mb_strtolower(uniqid('ty'));
        $sought = new VocabularyConcept(VocabularyType::METIER, 'Comptable '.$tag);
        $em->persist($sought);
        $this->exercise($em, $sought);
        $em->flush();

        $token = $this->loginToken(self::OWNER);

        // Un « p » en moins : le passage exact ne trouve rien, le secours rattrape.
        $this->request('GET', '/v1/suggestions?type=JOB&q=comtable '.$tag, $token);
        self::assertResponseIsSuccessful();
        self::assertSame(['Comptable '.$tag], array_column($this->jsonResponse()['items'], 'value'));

        // Bien orthographié, le passage exact suffit : la réponse est la même, sans secours.
        $this->request('GET', '/v1/suggestions?type=JOB&q=comptable '.$tag, $token);
        self::assertSame(['Comptable '.$tag], array_column($this->jsonResponse()['items'], 'value'));
    }

    /**
     * Sous cinq lettres, aucun rattrapage n'est tenté (task API-023).
     *
     * Un mot court porte trop peu de trigrammes pour être comparé : « exel » ne retrouve pas
     * « excel » mais ramène quatre métiers sans rapport. Le seuil ne sait pas séparer les deux, la
     * longueur si — et c'est elle qui garde la bouche fermée.
     */
    public function testAShortTermIsNeverRescued(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $tag = mb_strtolower(uniqid('sh'));
        $concept = new VocabularyConcept(VocabularyType::METIER, 'Comptable '.$tag);
        $em->persist($concept);
        $this->exercise($em, $concept);
        $em->flush();

        // « comt » ressemble assez à « comptable » pour être rattrapé — il ne l'est pas, il est trop
        // court. La même faute sur un mot long l'est, elle : cf. le test précédent.
        $this->request('GET', '/v1/suggestions?type=JOB&q=comt', $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();
        self::assertNotContains('Comptable '.$tag, array_column($this->jsonResponse()['items'], 'value'));
    }

    /**
     * Sans les extensions, les suggestions continuent de servir (task API-023).
     *
     * La base de test ne reçoit que `plpgsql` : ce test s'exécute donc dans l'état exact d'une
     * production qui n'aurait pas encore reçu `app:search:index`. Le mot au milieu tient — il ne
     * demande rien —, le rattrapage se tait, et rien ne tombe.
     */
    public function testWithoutTheExtensionsSuggestionsDegradeButNeverFail(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('dg'));

        $concept = new VocabularyConcept(VocabularyType::METIER, 'Comptable '.$tag);
        $em->persist($concept);
        $this->exercise($em, $concept);
        $em->flush();

        $token = $this->loginToken(self::OWNER);

        $this->request('GET', '/v1/suggestions?type=JOB&q='.$tag, $token);
        self::assertResponseIsSuccessful();
        self::assertSame(['Comptable '.$tag], array_column($this->jsonResponse()['items'], 'value'));

        $this->request('GET', '/v1/suggestions?type=JOB&q=comtable '.$tag, $token);
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->jsonResponse()['items'], 'sans pg_trgm, le secours ne se déclenche pas');
    }

    /**
     * Une espace de bord ne fait pas deux compétences (task API-023).
     *
     * Le champ « Mots-clés » proposait « python » et le même mot suivi d'une espace comme deux
     * entrées, chacune avec sa part des profils.
     */
    public function testTrailingSpacesDoNotSplitASkillInTwo(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('tr'));

        $this->persistProfile($em, ['Rust '.$tag]);
        $this->persistProfile($em, ['Rust '.$tag.' ']);
        $em->flush();

        $this->request('GET', '/v1/suggestions?type=SKILL&q=rust '.$tag, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        $items = $this->jsonResponse()['items'];
        self::assertCount(1, $items, 'le même mot, à une espace près, reste une seule proposition');
        self::assertArrayNotHasKey('profileCount', $items[0]);
    }

    /**
     * Le plus porté reste en tête, sans dire combien (task API-025).
     *
     * Une fois le nombre retiré de la réponse, l'ORDRE est la seule trace observable qu'il est
     * toujours calculé. Le départage alphabétique jouerait ici en sens INVERSE — « junior » précède
     * « senior » — donc seul le nombre de porteurs peut expliquer le résultat attendu.
     */
    public function testTheMostCarriedComesFirstWithoutSayingHowMany(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = mb_strtolower(uniqid('rk'));

        $rare = $this->concept($em, $tag.' junior');
        $common = $this->concept($em, $tag.' senior');
        $this->exercise($em, $rare);
        $this->exercise($em, $common);
        $this->exercise($em, $common);
        $em->flush();

        $this->request('GET', '/v1/suggestions?type=JOB&q='.$tag, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        $items = $this->jsonResponse()['items'];
        self::assertSame([$tag.' senior', $tag.' junior'], array_column($items, 'value'));
        self::assertArrayNotHasKey('profileCount', $items[0], 'le nombre de profils ne sort toujours pas du serveur');
    }

    public function testATermTooShortProposesNothing(): void
    {
        $this->request('GET', '/v1/suggestions?type=SKILL&q=a', $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();
        // Sur une lettre, une liste n'aide pas : elle occupe l'écran.
        self::assertSame([], $this->jsonResponse()['items']);
    }

    public function testWildcardsTypedByTheUserAreNotOperators(): void
    {
        // `%` en fin de saisie ne doit pas tout ramener : il est échappé, pas interprété.
        $this->request('GET', '/v1/suggestions?type=SKILL&q=%25', $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->jsonResponse()['items']);
    }

    private function concept(EntityManagerInterface $em, string $label): VocabularyConcept
    {
        $concept = new VocabularyConcept(VocabularyType::METIER, $label);
        $em->persist($concept);

        return $concept;
    }

    /** Un profil de plus exerçant ce métier — c'est le nombre de porteurs qui classe les propositions. */
    private function exercise(EntityManagerInterface $em, VocabularyConcept $concept): void
    {
        $candidate = $this->persistProfile($em, []);
        $candidate->getJobExperiences()->add(
            new CandidateJobExperience($candidate, $concept, months: 24, positions: 1, exactPositions: 1),
        );
    }

    /** @param list<string> $skills */
    private function persistProfile(EntityManagerInterface $em, array $skills): Candidate
    {
        $person = new Person('Test', 'Suggest-'.uniqid(), contactEmail: uniqid('sg-', true).'@example.test', country: 'FR', city: 'Paris');
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
        $em->persist(new CandidateJarvi($candidate, skills: $skills));

        return $candidate;
    }
}
