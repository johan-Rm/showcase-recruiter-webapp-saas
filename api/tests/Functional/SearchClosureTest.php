<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\SavedSearch;
use App\Entity\SearchView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Clôturer une recherche, et la rouvrir (task API-028).
 *
 * L'état n'est pas une étiquette : il **verrouille** l'écriture et **éteint** la nouveauté. Ce sont
 * ces deux conséquences qui lui donnent son sens, et elles se testent autant que le geste lui-même.
 *
 * Fixtures : « Finance France vers Casablanca » appartient à `atlas.owner` et elle est PARTAGÉE ;
 * `atlas.member` la lit sans pouvoir y écrire.
 */
final class SearchClosureTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';

    /** Le cycle complet : active → close → rouverte, et l'édition qui revient avec. */
    public function testTheAuthorClosesThenReopensAndEditingComesBack(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        self::assertNull($this->read($searchId, $token), 'une recherche naît active');

        $this->patch($searchId, $token, ['isClosed' => true]);
        self::assertResponseIsSuccessful();
        $closedAt = $this->read($searchId, $token);
        self::assertNotNull($closedAt, 'la clôture pose une date');

        $this->patch($searchId, $token, ['isClosed' => false]);
        self::assertResponseIsSuccessful();
        self::assertNull($this->read($searchId, $token), 'rouvrir efface la date');

        $this->patch($searchId, $token, ['name' => 'Reprise']);
        self::assertResponseIsSuccessful();
    }

    /**
     * Une recherche close refuse TOUT le reste, en 409 : la charge utile est valide, c'est l'état
     * qui la refuse. Rouvrir, lui, passe toujours.
     */
    public function testAClosedSearchRefusesEveryOtherChange(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $this->patch($searchId, $token, ['isClosed' => true]);
        self::assertResponseIsSuccessful();

        foreach ([
            'critères' => ['criteria' => [['facet' => 'country', 'value' => 'BE', 'group' => 'lieu']]],
            'nom' => ['name' => 'Tentative'],
            'partage' => ['isShared' => false],
        ] as $quoi => $payload) {
            $this->patch($searchId, $token, $payload);
            self::assertResponseStatusCodeSame(409, "modifier le {$quoi} d'une close doit être refusé");
        }

        $this->patch($searchId, $token, ['isClosed' => false]);
        self::assertResponseIsSuccessful();
    }

    /** Clôturer une recherche déjà close est l'affirmation d'un état : la date ne bouge pas. */
    public function testClosingAnAlreadyClosedSearchDoesNotMoveTheDate(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $this->patch($searchId, $token, ['isClosed' => true]);
        $first = $this->read($searchId, $token);

        $this->patch($searchId, $token, ['isClosed' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame($first, $this->read($searchId, $token));
    }

    /**
     * Lire n'a jamais autorisé à écrire : le destinataire d'une recherche partagée ne peut pas la
     * clôturer. Il la voit close, en revanche — l'état appartient à la recherche.
     */
    public function testAMemberCannotCloseASharedSearchButSeesItClosed(): void
    {
        $memberToken = $this->loginToken(self::MEMBER);
        $searchId = $this->demoSearchId();

        $this->request('GET', "/v1/searches/{$searchId}", $memberToken);
        self::assertResponseIsSuccessful();

        $this->patch($searchId, $memberToken, ['isClosed' => true]);
        self::assertResponseStatusCodeSame(403);

        $this->patch($searchId, $this->loginToken(self::OWNER), ['isClosed' => true]);
        self::assertResponseIsSuccessful();

        self::assertNotNull($this->read($searchId, $this->loginToken(self::MEMBER)));
    }

    /**
     * `newCount` s'éteint au serveur, et c'est bien la clôture qui l'éteint.
     *
     * Chaque écriture remet aussi le repère de lecture — ce qui suffirait à expliquer un zéro. On
     * vieillit donc le repère APRÈS coup, et on lit deux fois avec le MÊME repère : close, puis
     * rouverte. Seul l'état diffère.
     */
    public function testAClosedSearchStopsAnnouncingNovelty(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $this->patch($searchId, $token, ['isClosed' => true]);
        self::assertResponseIsSuccessful();
        $this->ageTheReadingMarker($searchId);
        self::assertSame(0, $this->newCount($searchId, $token), 'une close ne réclame plus rien');

        $this->patch($searchId, $token, ['isClosed' => false]);
        self::assertResponseIsSuccessful();
        $this->ageTheReadingMarker($searchId);
        self::assertGreaterThan(
            0,
            $this->newCount($searchId, $token),
            'rouverte et avec le même repère, la nouveauté réapparaît',
        );
    }

    /** Supprimer n'est pas modifier : une recherche close reste supprimable. */
    public function testAClosedSearchCanStillBeDeleted(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $this->patch($searchId, $token, ['isClosed' => true]);
        self::assertResponseIsSuccessful();

        $this->request('DELETE', "/v1/searches/{$searchId}", $token);
        self::assertResponseIsSuccessful();

        $this->request('GET', "/v1/searches/{$searchId}", $token);
        self::assertResponseStatusCodeSame(404);
    }

    /** Une recherche naît active : `isClosed` posé à la création est ignoré. */
    public function testASearchIsAlwaysBornActive(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier']],
            'isClosed' => true,
        ]);
        self::assertResponseIsSuccessful();

        self::assertNull($this->jsonResponse()['search']['closedAt'] ?? null);
    }

    /**
     * La date de clôture servie, ou `null`.
     *
     * API Platform ne sérialise pas les valeurs nulles : sur une recherche active, la clé est
     * ABSENTE de la réponse — ce n'est pas propre à ce champ, c'est le comportement de toute l'API.
     */
    private function read(string $searchId, string $token): ?string
    {
        $this->request('GET', "/v1/searches/{$searchId}", $token);
        self::assertResponseIsSuccessful();

        return $this->jsonResponse()['closedAt'] ?? null;
    }

    private function newCount(string $searchId, string $token): int
    {
        $this->request('GET', "/v1/searches/{$searchId}", $token);
        self::assertResponseIsSuccessful();

        return $this->jsonResponse()['newCount'];
    }

    /** @param array<string, mixed> $payload */
    private function patch(string $searchId, string $token, array $payload): void
    {
        $this->request('PATCH', "/v1/searches/{$searchId}", $token, $payload);
    }

    /** Recule le repère de lecture pour que tout le jeu redevienne « nouveau ». */
    private function ageTheReadingMarker(string $searchId): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['uuid' => $searchId]);
        self::assertInstanceOf(SavedSearch::class, $search);

        $em->createQuery(
            'UPDATE '.SearchView::class.' v SET v.lastViewedAt = :old WHERE v.search = :search',
        )
            ->setParameter('old', new DateTimeImmutable('2020-01-01'))
            ->setParameter('search', $search)
            ->execute();
        $em->clear();
    }

    private function demoSearchId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);

        return $search->getUuid()->toString();
    }
}
