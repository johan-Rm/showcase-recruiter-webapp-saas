<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\State\Provider\SearchProfilesProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Les fiches par lot, depuis des recherches nommées — `GET /v1/searches/profiles`, task `API-061`.
 *
 * Ce que ces tests protègent :
 *
 * 1. **Le dédoublonnage**, seule raison d'être du lot : un profil qui sert trois des recherches
 *    demandées ne voyage qu'une fois.
 * 2. **Les bornes réduisent au lieu de refuser** — une demande maladroite est servie amputée, pas
 *    rejetée.
 * 3. **Un lot ne tombe pas pour un mauvais identifiant** : c'est une commodité, pas une transaction.
 * 4. **Rien ne dit ce qui existe** : une recherche invisible et une recherche inconnue rendent la
 *    même réponse. C'est cette indistinction qui empêche de deviner.
 */
final class SearchProfilesTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string AUTRE = 'owner@demo.showcase-recruiter-webapp-saas.example';
    private const string ROUTE = '/v1/searches/profiles';
    private const string INCONNUE = '019ffff0-0000-7000-8000-000000000000';

    /** Le cœur du lot : deux recherches qui partagent des profils rendent une fiche par personne. */
    public function testProfilesAreDeduplicatedAcrossTheRequestedSearches(): void
    {
        $em = $this->em();
        $partages = $this->candidates($em, 4);
        $une = $this->searchWith($em, 'Une', $partages);
        $deux = $this->searchWith($em, 'Deux', $partages);
        $em->flush();

        $items = $this->profiles([$une, $deux]);

        self::assertCount(4, $items, 'quatre personnes, pas huit appartenances');
        self::assertSame(
            \count($items),
            \count(array_unique(array_column($items, 'id'))),
            'aucun doublon dans la réponse',
        );
    }

    /** Le nombre demandé est pris à CHAQUE recherche, pas au lot entier. */
    public function testTheTakeAppliesPerSearch(): void
    {
        $em = $this->em();
        $une = $this->searchWith($em, 'Une', $this->candidates($em, 5));
        $deux = $this->searchWith($em, 'Deux', $this->candidates($em, 5));
        $em->flush();

        self::assertCount(4, $this->profiles([$une, $deux], 2), 'deux de chacune, sans recouvrement');
    }

    /**
     * Les bornes RÉDUISENT.
     *
     * Une demande hors borne est une maladresse d'appelant, pas une faute : la servir amputée vaut
     * mieux qu'une erreur qu'il faudrait traiter dans un chargement de fond.
     */
    public function testAnOversizedRequestIsTrimmedRatherThanRefused(): void
    {
        $em = $this->em();
        $large = $this->searchWith($em, 'Large', $this->candidates($em, SearchProfilesProvider::MAX_TAKE + 5));
        $em->flush();

        self::assertCount(SearchProfilesProvider::MAX_TAKE, $this->profiles([$large], 9999));
    }

    /** Sans `take`, le serveur prend ce qu'il a décidé de prendre — l'appelant n'a rien à recopier. */
    public function testTheDefaultTakeApplies(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Défaut', $this->candidates($em, SearchProfilesProvider::DEFAULT_TAKE + 3));
        $em->flush();

        self::assertCount(SearchProfilesProvider::DEFAULT_TAKE, $this->profiles([$search]));
    }

    /** Un lot vide rend une collection vide, sans erreur : il n'y a rien à refuser. */
    public function testAnEmptyRequestReturnsAnEmptyCollection(): void
    {
        self::assertSame([], $this->items(self::ROUTE.'?take=30'));
    }

    /**
     * UN MAUVAIS IDENTIFIANT NE FAIT PAS TOMBER LE LOT.
     *
     * Un chargement de fond compose ses demandes à partir de ce qu'il affiche ; une recherche
     * supprimée entre-temps ne doit pas priver l'écran des trois autres.
     */
    public function testAnUnknownIdentifierIsIgnoredWithoutLosingTheRest(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Valide', $this->candidates($em, 3));
        $em->flush();

        $items = $this->items(\sprintf(
            '%s?ids=%s,%s&take=30',
            self::ROUTE,
            self::INCONNUE,
            $search->getUuid()->toString(),
        ));

        self::assertCount(3, $items, 'la recherche valide est servie malgré l\'identifiant inconnu');
    }

    /**
     * **RIEN NE DIT CE QUI EXISTE.**.
     *
     * La recherche d'autrui et une recherche inventée rendent exactement la même réponse. Refuser
     * l'une et ignorer l'autre reviendrait à révéler laquelle existe, par simple comparaison.
     */
    public function testAForeignSearchIsIndistinguishableFromAnUnknownOne(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'À moi', $this->candidates($em, 3));
        $em->flush();

        $token = $this->loginToken(self::AUTRE);

        $this->request('GET', self::ROUTE.'?ids='.$search->getUuid()->toString().'&take=30', $token);
        self::assertResponseIsSuccessful();
        $etrangere = $this->jsonResponse()['items'];

        $this->request('GET', self::ROUTE.'?ids='.self::INCONNUE.'&take=30', $token);
        self::assertResponseIsSuccessful();
        $inventee = $this->jsonResponse()['items'];

        self::assertSame([], $etrangere);
        self::assertSame($inventee, $etrangere, 'les deux réponses sont identiques');
    }

    /**
     * Les fiches n'appartiennent à AUCUNE recherche.
     *
     * L'état de travail est une relation (recherche × profil) : posé sur une fiche dédoublonnée, il
     * se mélangerait dès que deux recherches partagent un profil.
     */
    public function testCardsCarryNoSearchState(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Sans état', $this->candidates($em, 2));
        $em->flush();

        foreach ($this->profiles([$search]) as $card) {
            foreach (['isNew', 'isFavorite', 'isSeen', 'isDismissed', 'matches'] as $key) {
                self::assertNull($card[$key], "{$key} n'a de sens que dans une recherche");
            }
        }
    }

    /** Le masquage est celui des autres routes de profils : on paie pour joindre, pas pour lire. */
    public function testContactDetailsStayMasked(): void
    {
        $em = $this->em();
        $search = $this->searchWith($em, 'Masquée', $this->candidates($em, 2));
        $em->flush();

        foreach ($this->profiles([$search]) as $card) {
            self::assertNull($card['contactEmail']);
            self::assertNull($card['phone']);
            self::assertFalse($card['revealedEmail']);
        }
    }

    /**
     * @param list<SavedSearch> $searches
     *
     * @return list<array<string, mixed>>
     */
    private function profiles(array $searches, ?int $take = null): array
    {
        $ids = implode(',', array_map(static fn (SavedSearch $s): string => $s->getUuid()->toString(), $searches));

        return $this->items(self::ROUTE.'?ids='.$ids.(null === $take ? '' : '&take='.$take));
    }

    /** @return list<array<string, mixed>> */
    private function items(string $uri): array
    {
        $this->request('GET', $uri, $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>} $payload */
        $payload = $this->jsonResponse();

        return $payload['items'];
    }

    /** @param list<Candidate> $members */
    private function searchWith(EntityManagerInterface $em, string $name, array $members): SavedSearch
    {
        $owner = $this->recruiter(self::OWNER);
        $search = new SavedSearch($owner, $owner->organization, $name, [], isAutoNamed: false);
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
            $person = new Person('Lot', 'Profil-'.$suffix, contactEmail: "lot-{$suffix}@example.test", country: 'FR', city: 'Paris');
            $em->persist($person);

            $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
            $em->persist($candidate);
            // La réplica Jarvi n'est pas décorative : la mise en carte y lit les coordonnées quand la
            // personne n'en porte pas, et un profil qui n'en a aucune ferait tomber le mapping.
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
