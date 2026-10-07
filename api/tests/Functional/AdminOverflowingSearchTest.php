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
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * L'inventaire des recherches qui débordent — `GET /v1/admin/searches`, task `API-051`.
 *
 * Le recruteur ne voit que ce qu'on lui donne à travailler ; le nombre réel ne sort que par cet
 * écran. Ce que ces tests protègent : que **le seuil soit celui du chargement** — un inventaire avec
 * son propre seuil listerait des recherches vues entièrement, ou en tairait qui débordent —, que
 * l'ordre serve la lecture, et que la porte reste fermée à qui n'administre pas.
 *
 * Les jeux sont posés à la main : ce test porte sur l'inventaire, pas sur le moteur qui décide qui
 * entre dans un jeu.
 */
final class AdminOverflowingSearchTest extends ApiTestCase
{
    private const string ADMIN = 'admin@demo.showcase-recruiter-webapp-saas.example';
    private const string RECRUITER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string ROUTE = '/v1/admin/searches';

    public function testItListsWhatOverflowsAndIgnoresWhatFitsEntirely(): void
    {
        $em = $this->em();
        $pool = $this->candidates($em, SearchResultsBuilder::RECOMMENDED_LIMIT + 3);
        $large = $this->searchWith($em, 'Débordante', $pool);
        $petite = $this->searchWith($em, 'Tenue', \array_slice($pool, 0, 5));
        $em->flush();

        $ids = array_column($this->inventory()['items'], 'id');

        self::assertContains($large->getUuid()->toString(), $ids);
        self::assertNotContains(
            $petite->getUuid()->toString(),
            $ids,
            'une recherche que le recruteur voit entièrement ne déborde pas',
        );
    }

    /** Le seuil est CELUI DE LA RECOMMANDATION : exactement cent ne déborde pas, cent un si. */
    public function testTheThresholdIsTheLoadedLimitItself(): void
    {
        $em = $this->em();
        $pool = $this->candidates($em, SearchResultsBuilder::RECOMMENDED_LIMIT + 1);
        $pile = $this->searchWith($em, 'Pile la limite', \array_slice($pool, 0, SearchResultsBuilder::RECOMMENDED_LIMIT));
        $une = $this->searchWith($em, 'Une de trop', $pool);
        $em->flush();

        $ids = array_column($this->inventory()['items'], 'id');

        self::assertNotContains($pile->getUuid()->toString(), $ids);
        self::assertContains($une->getUuid()->toString(), $ids);
    }

    /** Du plus large au plus étroit : c'est l'ordre dans lequel on regarde ce genre de liste. */
    public function testItIsSortedByTheRealMatchCount(): void
    {
        $em = $this->em();
        $pool = $this->candidates($em, SearchResultsBuilder::RECOMMENDED_LIMIT + 10);
        $this->searchWith($em, 'La plus large', $pool);
        $this->searchWith($em, 'Un peu moins', \array_slice($pool, 0, SearchResultsBuilder::RECOMMENDED_LIMIT + 4));
        $em->flush();

        $counts = array_column($this->inventory()['items'], 'matchCount');

        self::assertGreaterThanOrEqual(2, \count($counts), 'deux recherches débordantes attendues');
        for ($i = 1, $total = \count($counts); $i < $total; ++$i) {
            self::assertGreaterThanOrEqual($counts[$i], $counts[$i - 1]);
        }
    }

    /**
     * Chaque ligne porte de quoi décider sans ouvrir la recherche.
     *
     * `servedCount` avec `matchCount` : servir le seul nombre réel obligerait l'écran à connaître la
     * borne pour dire l'écart, et cette borne serait alors écrite à deux endroits.
     */
    public function testEachRowCarriesWhatTheScreenNeeds(): void
    {
        $em = $this->em();
        $search = $this->searchWith(
            $em,
            '',
            $this->candidates($em, SearchResultsBuilder::RECOMMENDED_LIMIT + 2),
            [['facet' => 'job', 'value' => 'Développeur logiciel', 'group' => 'metier']],
        );
        $search->closedAt = new DateTimeImmutable();
        $em->flush();

        $row = $this->rowOf($this->inventory(), $search);

        self::assertSame(SearchResultsBuilder::RECOMMENDED_LIMIT + 2, $row['matchCount']);
        self::assertSame(SearchResultsBuilder::RECOMMENDED_LIMIT, $row['servedCount']);
        self::assertTrue($row['isClosed'], 'une recherche close qui débordait reste une information');
        self::assertSame('', $row['name'], 'le nom vide est servi tel quel');
        self::assertNotSame([], $row['criteria'], 'ce sont les critères qui en tiennent lieu');
        self::assertNotSame('', $row['organizationName']);
        self::assertNotSame('', $row['ownerName']);
        self::assertNotSame('', $row['lastActivityAt']);
    }

    public function testItPaginates(): void
    {
        $em = $this->em();
        $pool = $this->candidates($em, SearchResultsBuilder::RECOMMENDED_LIMIT + 5);
        $this->searchWith($em, 'Première', $pool);
        $this->searchWith($em, 'Seconde', $pool);
        $em->flush();

        $page = $this->inventory('?perPage=1');

        self::assertCount(1, $page['items']);
        self::assertSame(2, $page['total'], 'le total décrit la liste entière, pas la page');
        self::assertSame(1, $page['perPage']);
    }

    /** La porte est fermée à qui n'administre pas — et le silence vaut mieux qu'un refus bavard. */
    public function testItIsReservedToAdministrators(): void
    {
        $this->request('GET', self::ROUTE, $this->loginToken(self::RECRUITER));
        self::assertResponseStatusCodeSame(403);

        $this->request('GET', self::ROUTE);
        self::assertResponseStatusCodeSame(401);
    }

    /** Aucune recherche au-dessus du seuil : une collection vide, pas une erreur. */
    public function testAnInventoryWithoutOverflowIsEmptyAndValid(): void
    {
        $inventory = $this->inventory();

        self::assertSame([], $inventory['items']);
        self::assertSame(0, $inventory['total']);
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int} */
    private function inventory(string $query = ''): array
    {
        $this->request('GET', self::ROUTE.$query, $this->loginToken(self::ADMIN));
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>, total: int, page: int, perPage: int} $payload */
        $payload = $this->jsonResponse();

        return $payload;
    }

    /**
     * @param array{items: list<array<string, mixed>>} $inventory
     *
     * @return array<string, mixed>
     */
    private function rowOf(array $inventory, SavedSearch $search): array
    {
        foreach ($inventory['items'] as $row) {
            if ($row['id'] === $search->getUuid()->toString()) {
                return $row;
            }
        }

        self::fail('la recherche attendue ne figure pas dans l\'inventaire');
    }

    /**
     * @param list<Candidate>                                          $members
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    private function searchWith(EntityManagerInterface $em, string $name, array $members, array $criteria = []): SavedSearch
    {
        $owner = $this->recruiter(self::RECRUITER);
        $search = new SavedSearch($owner, $owner->organization, $name, $criteria, isAutoNamed: '' === $name);
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
            $person = new Person('Inventaire', 'Debord-'.$suffix, contactEmail: "debord-{$suffix}@example.test", country: 'FR', city: 'Paris');
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
