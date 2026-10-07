<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Favorite;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Repository\CandidateRepository;
use App\State\SearchResultsBuilder;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Le plan de travail d'un recruteur — `GET /v1/searches/workspace`, task `API-050`.
 *
 * Ce que ces tests protègent, dans l'ordre d'importance :
 *
 * 1. **La réponse ne porte QUE des identifiants.** Ni fiche, ni explication : c'est ce dépouillement
 *    qui l'a fait passer de 2,6 méga-octets à 115 kilo-octets sur un compte réel.
 * 2. **Les cinq listes décrivent la recherche entière**, jamais une page ni ce qui est chargé. Un
 *    compte déduit d'un chargement partiel rétrécirait avec lui.
 * 3. **Le nombre de requêtes ne croît pas avec le nombre de recherches.** Lue naïvement, cette route
 *    en fait autant que le plan contient de recherches — invisible en développement, fatal sur un
 *    compte chargé.
 */
final class SearchWorkspaceTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string WORKSPACE = '/v1/searches/workspace';

    public function testItServesOnlyTheOpenSearchesOfTheReader(): void
    {
        $em = $this->em();
        $ouverte = $this->searchWith($em, 'Ouverte', 2);
        $close = $this->searchWith($em, 'Close', 2);
        $close->closedAt = new DateTimeImmutable();
        $em->flush();

        $plan = $this->workspace();
        $ids = array_column($plan['searches'], 'id');

        self::assertContains($ouverte->getUuid()->toString(), $ids);
        self::assertNotContains($close->getUuid()->toString(), $ids, 'une recherche close ne fait pas partie du plan de travail');
    }

    /**
     * **LA RÉPONSE NE PORTE QUE DES IDENTIFIANTS.**.
     *
     * Ni fiche, ni explication : un identifiant coûte 39 octets, une fiche 1 200, une explication
     * 600. Les remettre ici ferait repayer le plus cher pour obtenir le moins cher, et c'est
     * exactement ce que cette route a cessé de faire.
     */
    public function testTheResponseCarriesIdentifiersAndNothingElse(): void
    {
        $em = $this->em();
        $partages = $this->candidates($em, 3);
        $this->searchWith($em, 'Première', 0, $partages);
        $this->searchWith($em, 'Seconde', 0, $partages);
        $em->flush();

        // Lue BRUTE et non par l'aide typée : c'est l'absence du pool dans la réponse qu'on vérifie,
        // et un type déclaré ne prouve rien de ce que le serveur envoie.
        $this->request('GET', self::WORKSPACE, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();
        $brut = $this->jsonResponse();

        self::assertSame(['searches'], array_keys($brut), 'aucun pool de fiches dans l\'enveloppe');

        $plan = $this->workspace();
        foreach ($plan['searches'] as $entry) {
            self::assertSame(
                ['id', 'resultIds', 'favoriteIds', 'dismissedIds', 'seenIds', 'newIds'],
                array_keys($entry),
                'cinq listes et un identifiant, rien de plus',
            );
        }

        // L'appartenance, elle, reste répétée : le même profil sert les deux recherches.
        $appartenances = array_merge(...array_column($plan['searches'], 'resultIds'));
        foreach ($partages as $candidate) {
            self::assertSame(
                2,
                \count(array_keys($appartenances, $candidate->getUuid()->toString(), true)),
                'un profil partagé appartient aux deux jeux',
            );
        }
    }

    /**
     * Le jeu de chaque recherche est ENTIER, aussi large soit-elle.
     *
     * Une liste tronquée ferait mentir tous les compteurs qui s'en déduisent : l'écran croirait la
     * recherche plus courte qu'elle n'est, et son parcours s'arrêterait avant la fin.
     */
    public function testEachSetCarriesTheWholeSearch(): void
    {
        $em = $this->em();
        $size = SearchResultsBuilder::RECOMMENDED_LIMIT + 5;
        $large = $this->searchWith($em, 'Large', $size);
        $em->flush();

        $entry = $this->entryOf($this->workspace(), $large);

        self::assertCount($size, $entry['resultIds']);
    }

    /**
     * **LE TEST QUI PROTÈGE LA ROUTE.**.
     *
     * Cinq recherches de plus ne doivent pas coûter cinq lectures de plus. La marge admise couvre les
     * requêtes que Doctrine émet pour des raisons qui ne dépendent pas du plan (identité, métadonnées)
     * — pas une par recherche.
     */
    public function testTheQueryCountDoesNotGrowWithTheNumberOfSearches(): void
    {
        $em = $this->em();
        $partages = $this->candidates($em, 4);
        $this->searchWith($em, 'Unique', 0, $partages);
        $em->flush();
        $uuids = array_map(static fn (Candidate $candidate): string => $candidate->getUuid()->toString(), $partages);

        $avecUne = $this->queryCountForWorkspace();

        // Les profils sont relus : une requête HTTP a eu lieu entre-temps, et les entités de tout à
        // l'heure ne sont plus gérées par le gestionnaire de ce test.
        $em = $this->em();
        $relus = self::getContainer()->get(CandidateRepository::class)->findByUuids($uuids);
        for ($i = 0; $i < 5; ++$i) {
            $this->searchWith($em, 'Plan '.$i, 0, $relus);
        }
        $em->flush();

        $avecSix = $this->queryCountForWorkspace();

        self::assertLessThanOrEqual(
            $avecUne + 2,
            $avecSix,
            \sprintf('%d requêtes pour une recherche, %d pour six : le compte suit le plan', $avecUne, $avecSix),
        );
    }

    /**
     * LES NOUVEAUX PROFILS SONT SERVIS, et non déduits des fiches reçues.
     *
     * Déduite, cette liste vaudrait « les nouveaux parmi ce qui est chargé » : juste tant que tout
     * est chargé, fausse dès qu'on ne charge qu'une tranche. La nouveauté est l'ÂGE du profil, un
     * fait partagé par toute l'organisation.
     */
    public function testNewProfilesAreServedRatherThanDeduced(): void
    {
        $em = $this->em();
        $profils = $this->candidates($em, 3);
        $search = $this->searchWith($em, 'Fraîcheur', 0, $profils);
        $em->flush();

        // Un profil VIEILLI à la main : la base de test naît avec ses fixtures, tout y est donc
        // récent, et la règle des sept jours ne se prouve qu'en datant un profil dans le passé.
        $ancien = $profils[0];
        $em->createQuery('UPDATE '.Candidate::class.' c SET c.createdAt = :vieux WHERE c.id = :id')
            ->setParameter('vieux', new DateTimeImmutable('-30 days'))
            ->setParameter('id', $ancien->getId())
            ->execute();

        $entry = $this->entryOf($this->workspace(), $search);

        self::assertCount(2, $entry['newIds'], 'les profils qui viennent d\'arriver');
        self::assertNotContains(
            $ancien->getUuid()->toString(),
            $entry['newIds'],
            'un profil ancien n\'est plus une nouveauté, même s\'il est dans le jeu',
        );
    }

    /**
     * LES RETENUS SUIVENT L'ORDRE DU JEU, pas celui de leur mise en favori.
     *
     * C'est ce qui permettra à l'écran de cesser de demander cette liste à part : servie dans un
     * autre ordre, l'onglet des retenus changerait de classement le jour où l'appel dédié
     * disparaîtrait, sans que personne ne l'ait décidé.
     */
    public function testRetainedFollowTheOrderOfTheSet(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Retenus', 4);
        $em->flush();

        $jeu = $this->entryOf($this->workspace(), $search)['resultIds'];
        $byUuid = [];
        foreach (self::getContainer()->get(CandidateRepository::class)->findByUuids([$jeu[0], $jeu[2]]) as $candidate) {
            $byUuid[$candidate->getUuid()->toString()] = $candidate;
        }

        // Retenus à REBOURS de l'ordre du jeu, et posés en base plutôt que par l'API : ce test porte
        // sur l'ORDRE rendu, pas sur les règles d'exposition qui gouvernent la mise en favori.
        // La recherche est RELUE : une requête HTTP a eu lieu entre-temps, et l'entité de tout à
        // l'heure n'est plus gérée par le gestionnaire de ce test.
        $em = $this->em();
        $owner = $this->recruiter(self::OWNER);
        $relue = $em->getRepository(SavedSearch::class)->findOneBy(['uuid' => $search->getUuid()]);
        self::assertInstanceOf(SavedSearch::class, $relue);

        foreach ([$jeu[2], $jeu[0]] as $uuid) {
            $em->persist(new Favorite($relue, $byUuid[$uuid], $owner));
        }
        $em->flush();

        self::assertSame([$jeu[0], $jeu[2]], $this->entryOf($this->workspace(), $search)['favoriteIds']);
    }

    /** Un compte sans recherche ouverte reçoit un plan vide, pas une erreur. */
    public function testAnAccountWithoutOpenSearchesGetsAnEmptyPlan(): void
    {
        $em = $this->em();
        foreach ($em->getRepository(SavedSearch::class)->findAll() as $search) {
            $search->closedAt = new DateTimeImmutable();
        }
        $em->flush();

        $plan = $this->workspace();

        self::assertSame([], $plan['searches']);
    }

    /** @return array{searches: list<array<string, mixed>>} */
    private function workspace(): array
    {
        $this->request('GET', self::WORKSPACE, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        /** @var array{searches: list<array<string, mixed>>} $plan */
        $plan = $this->jsonResponse();

        return $plan;
    }

    private function queryCountForWorkspace(): int
    {
        // Le jeton AVANT d'armer le profileur : `enableProfiler()` ne vaut que pour la requête
        // suivante, et la connexion en est une — sans cette ligne, on compterait les requêtes du
        // login et le plan de travail passerait sans profil.
        $token = $this->loginToken(self::OWNER);

        $this->client->enableProfiler();
        $this->request('GET', self::WORKSPACE, $token);
        self::assertResponseIsSuccessful();

        $profile = $this->client->getProfile();
        self::assertNotFalse($profile, 'le profileur doit être armé pour compter les requêtes');
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        return $collector->getQueryCount();
    }

    /**
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    private function entryOf(array $plan, SavedSearch $search): array
    {
        /** @var list<array<string, mixed>> $searches */
        $searches = $plan['searches'];
        foreach ($searches as $entry) {
            if ($entry['id'] === $search->getUuid()->toString()) {
                return $entry;
            }
        }

        self::fail('la recherche attendue ne figure pas dans le plan de travail');
    }

    /**
     * Une recherche de l'auteur, avec des profils à elle et, si on veut, des profils partagés.
     *
     * Les appartenances sont posées à la main : ce test porte sur l'assemblage du plan, pas sur le
     * moteur qui décide qui entre dans un jeu.
     *
     * @param list<Candidate>                                          $shared
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    private function searchWith(
        EntityManagerInterface $em,
        string $name,
        int $own,
        array $shared = [],
        array $criteria = [],
    ): SavedSearch {
        $owner = $this->recruiter(self::OWNER);
        $search = new SavedSearch($owner, $owner->organization, $name, $criteria, isAutoNamed: false);
        $em->persist($search);

        foreach ([...$this->candidates($em, $own), ...$shared] as $candidate) {
            $em->persist(new SearchResultMembership($search, $candidate));
        }

        return $search;
    }

    /** @return list<Candidate> */
    private function candidates(EntityManagerInterface $em, int $count): array
    {
        $created = [];
        for ($i = 0; $i < $count; ++$i) {
            $suffix = uniqid('', true);
            $person = new Person('Plan', 'Travail-'.$suffix, contactEmail: "plan-{$suffix}@example.test", country: 'FR', city: 'Paris');
            $em->persist($person);

            $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
            $em->persist($candidate);
            // La réplica pro n'est pas décorative : la mise en carte y lit les coordonnées quand la
            // personne n'en porte pas.
            $em->persist(new CandidateJarvi($candidate));

            $created[] = $candidate;
        }

        return $created;
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
