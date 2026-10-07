<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\State\SearchResultsBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Les explications d'une recherche, page par page — `GET /v1/searches/{id}/explanations`,
 * task `API-062`.
 *
 * Ce que ces tests protègent :
 *
 * 1. **L'ordre est celui du jeu.** C'est la condition d'utilisabilité de la route : l'appelant tient
 *    déjà la liste des identifiants, il doit pouvoir recoller une page d'explications dessus sans
 *    rien recalculer. Deux ordres différents rendraient ces pages muettes.
 * 2. **La parité avec ce que la carte portait.** Cette route déplace une donnée, elle ne la change
 *    pas : un écran qui lisait l'explication sur la fiche doit lire exactement la même ici.
 * 3. **La garde est celle de la recherche**, et elle ne laisse rien deviner — invisible et
 *    inexistante rendent la même réponse.
 * 4. **Les bornes réduisent** au lieu de refuser, et une page au-delà de la fin est un cas normal.
 */
final class SearchExplanationsTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string AUTRE = 'owner@demo.showcase-recruiter-webapp-saas.example';
    private const string INCONNUE = '019ffff0-0000-7000-8000-000000000000';

    /** Le pays est la facette la plus sobre : elle s'explique sans dictionnaire ni parcours. */
    private const array CRITERE_PAYS = [['facet' => 'country', 'value' => 'FR', 'group' => 'lieu']];

    /**
     * L'ORDRE DU JEU, page après page — la condition d'utilisabilité de la route.
     *
     * L'appelant tient la liste des identifiants servie par la route d'état ; une page d'explications
     * doit s'y recoller telle quelle.
     */
    public function testEachPageFollowsTheOrderOfTheSet(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Ordonnée', $this->candidates($em, 7), self::CRITERE_PAYS);
        $em->flush();

        $jeu = $this->resultIds($search);
        $premiere = array_column($this->page($search, 1, 3)['items'], 'id');
        $seconde = array_column($this->page($search, 2, 3)['items'], 'id');

        self::assertSame(\array_slice($jeu, 0, 3), $premiere, 'la page 1 est le début du jeu');
        self::assertSame(\array_slice($jeu, 3, 3), $seconde, 'la page 2 enchaîne, sans recouvrement');
    }

    /** Le total est celui du JEU ENTIER : c'est lui qui dit combien de pages restent à demander. */
    public function testTheTotalDescribesTheWholeSetAndNotThePage(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Comptée', $this->candidates($em, 9), self::CRITERE_PAYS);
        $em->flush();

        $page = $this->page($search, 1, 4);

        self::assertSame(9, $page['total']);
        self::assertCount(4, $page['items']);
    }

    /**
     * CHAQUE ENTRÉE PORTE LE CRITÈRE ET LE LIBELLÉ DU PROFIL qui le satisfait.
     *
     * Les deux sont distincts par nature — on cherche « développeur », le profil dit « Frontend
     * developer ». Servir le seul critère cherché n'apprendrait rien de plus que la puce affichée.
     */
    public function testAnEntryCarriesTheCriterionAndWhatSatisfiesIt(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Expliquée', $this->candidates($em, 1), self::CRITERE_PAYS);
        $em->flush();

        $entree = $this->page($search)['items'][0];

        self::assertArrayHasKey('id', $entree);
        self::assertSame('country', $entree['matches'][0]['facet']);
        self::assertSame('FR', $entree['matches'][0]['value']);
        self::assertTrue($entree['matches'][0]['matched']);
        self::assertSame('FR', $entree['matches'][0]['matchedOn'], 'ce que porte LE PROFIL');
    }

    /**
     * LA MÊME EXPLICATION QUE CELLE QUE LA CARTE PORTAIT.
     *
     * Cette route déplace une donnée, elle ne la redéfinit pas : sans cette parité, un écran
     * afficherait deux verdicts différents selon qu'il a chargé les fiches avec leur explication ou
     * demandé cette page — et rien ne dirait lequel des deux est faux.
     */
    public function testTheExplanationMatchesTheOneCarriedByTheCard(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Parité', $this->candidates($em, 3), self::CRITERE_PAYS);
        $em->flush();

        $parCarte = [];
        foreach ($this->results($search) as $carte) {
            $parCarte[$carte['id']] = $carte['matches'];
        }

        foreach ($this->page($search)['items'] as $entree) {
            self::assertSame($parCarte[$entree['id']], $entree['matches']);
        }
    }

    /**
     * UN PROFIL QUE RIEN N'EXPLIQUE N'A PAS D'ENTRÉE.
     *
     * `languageLevel` ne vaut rien seul — il précise la facette `language`, qui porte déjà le niveau
     * atteint. Une recherche qui n'aurait que lui ne peut rien énoncer, et une entrée vide dirait la
     * même chose en plus lourd.
     */
    public function testAProfileNothingCanExplainHasNoEntry(): void
    {
        $em = $this->em();
        $search = $this->searchWith(
            $em,
            'Inexplicable',
            $this->candidates($em, 3),
            [['facet' => 'languageLevel', 'value' => 'C1', 'group' => 'langue']],
        );
        $em->flush();

        $page = $this->page($search);

        self::assertSame([], $page['items'], 'rien à énoncer, donc aucune entrée');
        self::assertSame(3, $page['total'], 'le jeu, lui, existe toujours');
    }

    /** Une page au-delà de la fin est un cas normal, pas une erreur : on déroule jusqu'à la fin. */
    public function testAPageBeyondTheEndReturnsAnEmptyCollection(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Courte', $this->candidates($em, 2), self::CRITERE_PAYS);
        $em->flush();

        $page = $this->page($search, 999);

        self::assertSame([], $page['items']);
        self::assertSame(2, $page['total'], 'le total ne dépend pas de la page demandée');
    }

    /** Les bornes de page sont celles du jeu de résultats : une seule règle dans tout le produit. */
    public function testPageSizeIsClampedRatherThanRefused(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Bornée', $this->candidates($em, 2), self::CRITERE_PAYS);
        $em->flush();

        self::assertSame(SearchResultsBuilder::MAX_PER_PAGE, $this->page($search, 1, 9999)['perPage']);
        self::assertSame(1, $this->page($search, 1, 0)['perPage']);
        self::assertSame(SearchResultsBuilder::DEFAULT_PER_PAGE, $this->page($search)['perPage']);
    }

    /**
     * LA GARDE EST CELLE DE LA RECHERCHE, et elle ne laisse rien deviner.
     *
     * Invisible et inexistante rendent le même 404 : c'est cette indistinction qui empêche de sonder
     * ce qui existe chez les autres.
     */
    public function testExplanationsAreGuardedLikeTheSearchItself(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Gardée', $this->candidates($em, 2), self::CRITERE_PAYS);
        $em->flush();

        $this->request('GET', $this->route($search), $this->loginToken(self::AUTRE));
        self::assertResponseStatusCodeSame(404, 'la recherche d\'une autre organisation reste invisible');

        $this->request('GET', '/v1/searches/'.self::INCONNUE.'/explanations', $this->loginToken(self::AUTRE));
        self::assertResponseStatusCodeSame(404, 'et une recherche inexistante répond pareil');
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int} */
    private function page(SavedSearch $search, ?int $page = null, ?int $perPage = null): array
    {
        $query = array_filter(['page' => $page, 'perPage' => $perPage], static fn (?int $v): bool => null !== $v);
        $uri = $this->route($search).([] === $query ? '' : '?'.http_build_query($query));

        $this->request('GET', $uri, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>, total: int, page: int, perPage: int} $payload */
        $payload = $this->jsonResponse();

        return $payload;
    }

    /**
     * Le jeu tel que le serveur l'ordonne — la référence à laquelle les pages doivent se recoller.
     *
     * @return list<string>
     */
    private function resultIds(SavedSearch $search): array
    {
        $this->request('GET', '/v1/searches/'.$search->getUuid()->toString().'/results', $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        /** @var array{resultIds: list<string>} $payload */
        $payload = $this->jsonResponse();

        return $payload['resultIds'];
    }

    /** @return list<array<string, mixed>> */
    private function results(SavedSearch $search): array
    {
        $this->request('GET', '/v1/searches/'.$search->getUuid()->toString().'/results', $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>} $payload */
        $payload = $this->jsonResponse();

        return $payload['items'];
    }

    private function route(SavedSearch $search): string
    {
        return '/v1/searches/'.$search->getUuid()->toString().'/explanations';
    }

    /**
     * @param list<Candidate>                                          $members
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    private function searchWith(EntityManagerInterface $em, string $name, array $members, array $criteria): SavedSearch
    {
        $owner = $this->recruiter(self::OWNER);
        $search = new SavedSearch($owner, $owner->organization, $name, $criteria, isAutoNamed: false);
        $em->persist($search);

        foreach ($members as $candidate) {
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
            $person = new Person('Explication', 'Profil-'.$suffix, contactEmail: "expl-{$suffix}@example.test", country: 'FR', city: 'Paris');
            $em->persist($person);

            $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
            $em->persist($candidate);
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
