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
 * UNE RECHERCHE SE PARCOURT JUSQU'AU DERNIER PROFIL, page par page (task `API-059`).
 *
 * Le service a longtemps borné ce qu'il servait : au-delà du centième rang, les profils étaient
 * classés, comptés, annoncés — et inatteignables. Ce nombre est devenu un **conseil** affiché au
 * recruteur ; il ne coupe plus rien.
 *
 * Ce que ces tests protègent : qu'aucune coupe ne revienne se glisser entre le jeu calculé et le jeu
 * servi. Ils traversent délibérément l'ancien mur — une page dont les rangs encadrent le centième —
 * parce que c'est exactement là qu'une borne oubliée se verrait.
 */
final class SearchPaginationTest extends ApiTestCase
{
    private const string OWNER = 'owner@demo.showcase-recruiter-webapp-saas.example';

    /** De combien on dépasse le seuil recommandé : assez pour prouver, pas plus — chaque profil est une insertion. */
    private const int OVERFLOW = 5;

    private const int PER_PAGE = 30;

    /** Le jeu ENTIER se parcourt, et le mur du centième rang se traverse sans rien perdre. */
    public function testASearchBeyondTheRecommendationIsBrowsedToItsLastProfile(): void
    {
        $searchId = $this->seedLargeSearch();
        $token = $this->loginToken(self::OWNER);
        $expected = SearchResultsBuilder::RECOMMENDED_LIMIT + self::OVERFLOW;

        $seen = [];
        for ($page = 1; $page <= 10; ++$page) {
            $items = $this->page($searchId, $token, $page)['items'];
            if ([] === $items) {
                break;
            }

            foreach ($items as $card) {
                $seen[$card['id']] = true;
            }
        }

        self::assertCount($expected, $seen, 'chaque profil du jeu est atteignable');
    }

    /**
     * LA PAGE QUI TRAVERSE LE CENTIÈME RANG rend ce qui suit, et dans le bon ordre.
     *
     * Avec des pages de trente, la quatrième couvre les rangs 91 à 105 : sous l'ancienne borne elle
     * se serait arrêtée au dixième de ses profils. C'est le test qui tombe en premier si une coupe
     * revient — et il vérifie l'identité des profils, pas seulement leur nombre, pour qu'une coupe
     * compensée par un décalage ne passe pas inaperçue.
     */
    public function testThePageCrossingTheOldWallCarriesWhatFollows(): void
    {
        $searchId = $this->seedLargeSearch();
        $token = $this->loginToken(self::OWNER);
        $first = $this->page($searchId, $token, 1);
        $offset = 3 * self::PER_PAGE;

        $fourth = $this->page($searchId, $token, 4);

        self::assertSame(
            \array_slice($first['resultIds'], $offset),
            array_column($fourth['items'], 'id'),
            'la quatrième page porte exactement les rangs 91 et suivants',
        );
        self::assertGreaterThan(
            SearchResultsBuilder::RECOMMENDED_LIMIT,
            $offset + \count($fourth['items']),
            'et elle va bien au-delà du centième rang',
        );
    }

    /** La liste ordonnée porte le jeu entier : c'est elle qui dit au client où il va avant d'y arriver. */
    public function testTheOrderedListCarriesTheWholeSet(): void
    {
        $searchId = $this->seedLargeSearch();
        $token = $this->loginToken(self::OWNER);
        $first = $this->page($searchId, $token, 1);

        self::assertCount($first['total'], $first['resultIds'], 'aucune coupe entre le total et la liste');
        self::assertSame(
            SearchResultsBuilder::RECOMMENDED_LIMIT + self::OVERFLOW,
            $first['total'],
            'le total porte le nombre réel de correspondances',
        );
    }

    /**
     * Une page au-delà de la fin revient VIDE, et surtout pas en erreur : un client qui déroule ne
     * doit pas rencontrer d'échec là où il n'y a simplement plus rien.
     */
    public function testAPageBeyondTheEndComesBackEmptyAndStillTellsTheTotal(): void
    {
        $searchId = $this->seedLargeSearch();
        $token = $this->loginToken(self::OWNER);

        $page = $this->page($searchId, $token, 20);

        self::assertSame([], $page['items']);
        self::assertSame(
            SearchResultsBuilder::RECOMMENDED_LIMIT + self::OVERFLOW,
            $page['total'],
            'le total ne dépend pas de la page demandée',
        );
    }

    /**
     * L'ORDRE NE BOUGE PAS d'un appel à l'autre.
     *
     * Sans cette garantie, un profil pourrait changer de page entre deux chargements : vu deux fois
     * par le recruteur, ou jamais.
     */
    public function testTheOrderIsStableAcrossCalls(): void
    {
        $searchId = $this->seedLargeSearch();
        $token = $this->loginToken(self::OWNER);

        self::assertSame(
            $this->page($searchId, $token, 1)['resultIds'],
            $this->page($searchId, $token, 1)['resultIds'],
        );
    }

    /** Les écartés quittent le jeu, et le total le dit — la pagination porte sur ce qui reste. */
    public function testDismissingShrinksTheSet(): void
    {
        $searchId = $this->seedLargeSearch();
        $token = $this->loginToken(self::OWNER);
        $before = $this->page($searchId, $token, 1);

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => \array_slice($before['resultIds'], 0, 3),
            'action' => 'DISMISS',
        ]);
        self::assertResponseIsSuccessful();

        $after = $this->page($searchId, $token, 1);
        self::assertSame($before['total'] - 3, $after['total']);
        self::assertCount($after['total'], $after['resultIds']);
    }

    /**
     * LE SEUIL RECOMMANDÉ voyage dans la configuration publique — et ne borne rien.
     *
     * Sans ce canal, l'écran recopierait le nombre pour afficher son conseil ; c'est exactement ce
     * que cette ressource existe pour éviter.
     */
    public function testTheRecommendationThresholdTravelsInTheConfiguration(): void
    {
        $this->request('GET', '/v1/config');
        self::assertResponseIsSuccessful();

        self::assertSame(SearchResultsBuilder::RECOMMENDED_LIMIT, $this->jsonResponse()['searchLoadLimit']);
    }

    /**
     * Une recherche dont le jeu dépasse le seuil recommandé, matérialisée à la main.
     *
     * À la main et non par le recalculateur : ces tests portent sur la pagination du SERVICE, pas sur
     * les critères. Les fixtures n'ont pas cent profils, et en faire correspondre cent à des critères
     * coûterait cent profils crédibles pour prouver un parcours qui ne les regarde pas.
     */
    private function seedLargeSearch(): string
    {
        $em = $this->em();
        $owner = $this->recruiter(self::OWNER);

        $search = new SavedSearch($owner, $owner->organization, 'Recherche large', [
            ['facet' => 'q', 'value' => 'borne', 'group' => 'motscles'],
        ]);
        $em->persist($search);

        for ($rank = 0; $rank < SearchResultsBuilder::RECOMMENDED_LIMIT + self::OVERFLOW; ++$rank) {
            $person = new Person('Profil', 'Borne-'.$rank, contactEmail: "borne-{$rank}@example.test", country: 'FR', city: 'Paris');
            $em->persist($person);

            $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
            $em->persist($candidate);
            // La réplica Jarvi n'est pas décorative : la mise en carte y lit les coordonnées quand la
            // personne n'en porte pas, et un profil qui n'en a aucune ferait tomber le mapping.
            $em->persist(new CandidateJarvi($candidate));
            $em->persist(new SearchResultMembership($search, $candidate));
        }

        $em->flush();

        return $search->getUuid()->toString();
    }

    /** @return array<string, mixed> */
    private function page(string $searchId, string $token, int $page): array
    {
        $this->request('GET', "/v1/searches/{$searchId}/results?page={$page}&perPage=".self::PER_PAGE, $token);
        self::assertResponseIsSuccessful();

        return $this->jsonResponse();
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
