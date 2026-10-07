<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qu'une recherche annonce de son propre parcours — `seenCount` et `favoriteCount` (task
 * API-027).
 *
 * Les deux nombres répondent à deux questions distinctes, et c'est ce qui explique leurs règles
 * opposées : « où en suis-je » est PERSONNEL et BORNÉ au jeu courant, « qu'est-ce que ça a produit »
 * est celui de la recherche et ne s'y borne pas.
 *
 * Fixtures : « Finance France vers Casablanca » appartient à `atlas.owner`, elle est PARTAGÉE, et
 * elle retient ses deux profils. `atlas.member` la lit sans pouvoir y écrire.
 */
final class SearchCountsTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';

    /**
     * `favoriteCount` doit égaler, au profil près, ce que sert `/favorites` — c'est la règle que
     * `SavedSearchOutput` pose déjà pour `resultCount` : la carte annonce ce que la Table servira.
     */
    public function testTheCardAnnouncesExactlyWhatTheTableWillServe(): void
    {
        $token = $this->loginToken(self::OWNER);
        $card = $this->card($token);

        $this->request('GET', "/v1/searches/{$card['id']}/favorites", $token);
        self::assertResponseIsSuccessful();

        self::assertSame($this->jsonResponse()['total'], $card['favoriteCount']);
        self::assertGreaterThan(0, $card['favoriteCount'], 'fixtures trop maigres pour ce test');
    }

    /** Une recherche qu'on vient d'ouvrir n'a rien vu et rien retenu. */
    public function testAFreshSearchCountsNothing(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier']],
        ]);
        self::assertResponseIsSuccessful();

        $search = $this->jsonResponse()['search'];
        self::assertSame(0, $search['seenCount']);
        self::assertSame(0, $search['favoriteCount']);
    }

    /**
     * L'avancement est celui du LECTEUR, comme `newCount` : deux recruteurs sur la même recherche
     * partagée n'en sont pas au même point, et rien ne le dirait s'ils lisaient le même nombre.
     *
     * Les retenus, eux, ne bougent pas d'un lecteur à l'autre : le partage est en lecture seule,
     * il n'y a qu'un auteur pour en poser.
     */
    public function testTheSeenCountBelongsToTheReaderAndTheRetainedToTheSearch(): void
    {
        $ownerToken = $this->loginToken(self::OWNER);
        $searchId = $this->card($ownerToken)['id'];

        $profiles = $this->resultIds($searchId, $ownerToken);
        self::assertGreaterThanOrEqual(2, \count($profiles), 'fixtures trop maigres pour ce test');

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $ownerToken, [
            'candidateIds' => $profiles,
            'action' => 'SEEN',
        ]);
        self::assertResponseIsSuccessful();

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $this->loginToken(self::MEMBER), [
            'candidateIds' => [$profiles[0]],
            'action' => 'SEEN',
        ]);
        self::assertResponseIsSuccessful();

        $forOwner = $this->card($this->loginToken(self::OWNER));
        $forMember = $this->card($this->loginToken(self::MEMBER));

        self::assertSame(\count($profiles), $forOwner['seenCount']);
        self::assertSame(1, $forMember['seenCount'], 'le membre ne lit que SON avancement');
        self::assertSame(
            $forOwner['favoriteCount'],
            $forMember['favoriteCount'],
            'les retenus appartiennent à la recherche, pas au lecteur',
        );
    }

    /**
     * LE cas qui sépare les deux règles.
     *
     * Le jeu de résultats rétrécit — `SearchMembershipRecalculator` retire la ligne d'un profil qui
     * cesse de correspondre — alors que la revue et le favori survivent. Sans la jointure sur
     * l'appartenance, l'avancement dépasserait le total et la barre afficherait plus de 100 %.
     */
    public function testAProfileLeavingTheSetDropsFromSeenButNotFromRetained(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->card($token)['id'];

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => $this->resultIds($searchId, $token),
            'action' => 'SEEN',
        ]);
        self::assertResponseIsSuccessful();

        $before = $this->card($token);
        self::assertGreaterThan(0, $before['seenCount']);
        self::assertGreaterThan(0, $before['favoriteCount']);

        // Un critère que personne ne satisfait : tout le monde quitte le jeu, personne n'est
        // « déretenu » pour autant.
        $this->request('PATCH', "/v1/searches/{$searchId}", $token, [
            'criteria' => [['facet' => 'country', 'value' => 'JP', 'group' => 'lieu']],
        ]);
        self::assertResponseIsSuccessful();

        $after = $this->card($token);
        self::assertSame(0, $after['resultCount']);
        self::assertSame(0, $after['seenCount'], 'un profil hors du jeu ne compte plus dans l’avancement');
        self::assertSame(
            $before['favoriteCount'],
            $after['favoriteCount'],
            'il reste retenu : la Table le montre toujours',
        );

        $this->request('GET', "/v1/searches/{$searchId}/favorites", $token);
        self::assertSame($this->jsonResponse()['total'], $after['favoriteCount']);
    }

    /**
     * Le prix des deux comptages : DEUX requêtes, quel que soit le nombre de recherches.
     *
     * On ne fige pas le total de la page — il croît d'une requête par recherche à cause de
     * `SearchNewCountReader`, qui compte depuis un repère de lecture différent pour chacune et
     * l'assume. On fige ce que CETTE tâche a ajouté, et rien d'autre.
     */
    public function testTheTwoCountsCostTwoQueriesWhateverTheNumberOfSearches(): void
    {
        $token = $this->loginToken(self::OWNER);
        // Sans ça, le noyau redémarre après chaque requête et le collecteur lu après l'appel n'est
        // plus celui qui l'a servi : la mesure sortirait vide.
        $this->client->disableReboot();

        $one = $this->countQueriesOnCollection($token);

        for ($i = 0; $i < 5; ++$i) {
            $this->request('POST', '/v1/searches', $token, [
                'criteria' => [['facet' => 'q', 'value' => 'mesure'.$i, 'group' => 'mot']],
            ]);
            self::assertResponseIsSuccessful();
        }

        $several = $this->countQueriesOnCollection($token);

        foreach (['search_profile_review', 'favorite'] as $table) {
            self::assertSame(1, $one[$table], "un seul lot suffit à compter {$table}");
            self::assertSame(1, $several[$table], "{$table} reste à une requête quand les recherches se multiplient");
        }
    }

    /**
     * Combien de `SELECT` par table sur `GET /v1/searches`.
     *
     * @return array{search_profile_review: int, favorite: int}
     */
    private function countQueriesOnCollection(string $token): array
    {
        $holder = self::getContainer()->get('doctrine.debug_data_holder');
        $holder->reset();

        $this->request('GET', '/v1/searches', $token);
        self::assertResponseIsSuccessful();

        $counts = ['search_profile_review' => 0, 'favorite' => 0];
        foreach ($holder->getData() as $queries) {
            foreach ($queries as $query) {
                $sql = \is_string($query['sql'] ?? null) ? $query['sql'] : '';
                // `favorite` seule : `search_profile_review` la contient comme sous-chaîne d'aucun
                // mot, mais un `FROM favorite` et un `FROM search_profile_review` se distinguent.
                if (str_contains($sql, 'FROM search_profile_review')) {
                    ++$counts['search_profile_review'];
                } elseif (str_contains($sql, 'FROM favorite')) {
                    ++$counts['favorite'];
                }
            }
        }

        return $counts;
    }

    /** @return array<string, mixed> la carte de la recherche partagée des fixtures */
    private function card(string $token): array
    {
        $this->request('GET', '/v1/searches', $token);
        self::assertResponseIsSuccessful();

        $wanted = $this->demoSearchId();
        foreach ($this->jsonResponse()['items'] as $item) {
            if ($wanted === $item['id']) {
                return $item;
            }
        }

        self::fail('la recherche de démonstration ne figure pas dans la collection');
    }

    /** @return list<string> */
    private function resultIds(string $searchId, string $token): array
    {
        $this->request('GET', "/v1/searches/{$searchId}/results?perPage=200", $token);
        self::assertResponseIsSuccessful();

        return $this->jsonResponse()['resultIds'];
    }

    private function demoSearchId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);

        return $search->getUuid()->toString();
    }
}
