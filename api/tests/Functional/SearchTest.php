<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Person;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Entity\SearchView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Recherche persistée, partage en lecture seule et badge de nouveautés (tâche 072).
 *
 * Fixtures : la recherche « Finance France vers Casablanca » appartient à atlas.owner et est
 * PARTAGÉE ; atlas.member est de la même organisation ; owner@demo est d'une AUTRE organisation.
 */
final class SearchTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';
    private const string OUTSIDER = 'owner@demo.showcase-recruiter-webapp-saas.example';

    public function testOwnerCreatesRenamesAndDeletesSearch(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'sector', 'value' => 'Informatique', 'group' => 'metier']],
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->jsonResponse()['search'];
        $searchId = $created['id'];

        // Sans nom fourni, le nom auto reste actif (le front le compose depuis les critères).
        self::assertTrue($created['isAutoNamed']);
        self::assertFalse($created['isShared']);
        self::assertTrue($created['isOwn']);

        $this->request('PATCH', "/v1/searches/{$searchId}", $token, ['name' => 'Mes devs']);
        self::assertResponseIsSuccessful();
        $renamed = $this->jsonResponse()['search'];
        self::assertSame('Mes devs', $renamed['name']);
        self::assertFalse($renamed['isAutoNamed'], 'Renommer manuellement désactive le nom auto.');

        // Vider le nom réactive le nom auto.
        $this->request('PATCH', "/v1/searches/{$searchId}", $token, ['name' => '']);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['search']['isAutoNamed']);

        $this->request('DELETE', "/v1/searches/{$searchId}", $token);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', "/v1/searches/{$searchId}", $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testCriteriaOrderIsPreserved(): void
    {
        $token = $this->loginToken(self::OWNER);
        $criteria = [
            ['facet' => 'presenceCity', 'value' => 'Casablanca', 'group' => 'presence'],
            ['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier'],
            ['facet' => 'availability', 'value' => 'IMMEDIATE', 'group' => 'dispo'],
        ];

        $this->request('POST', '/v1/searches', $token, ['criteria' => $criteria]);
        self::assertResponseStatusCodeSame(201);
        $searchId = $this->jsonResponse()['search']['id'];

        $this->request('GET', "/v1/searches/{$searchId}", $token);
        self::assertResponseIsSuccessful();
        // L'ordre de sélection est signifiant : il produit le nom auto côté front.
        self::assertSame($criteria, $this->jsonResponse()['criteria']);
    }

    public function testListReturnsOwnSearchesPlusSharedOnesWithAuthor(): void
    {
        $token = $this->loginToken(self::MEMBER);

        $this->request('GET', '/v1/searches', $token);
        self::assertResponseIsSuccessful();
        $items = $this->jsonResponse()['items'];

        self::assertCount(1, $items, 'Le membre ne voit que la recherche partagée de son collègue.');
        self::assertSame('Finance France vers Casablanca', $items[0]['name']);
        self::assertSame('Omar Alaoui', $items[0]['ownerName'], "L'auteur est exposé.");
        self::assertFalse($items[0]['isOwn']);
    }

    public function testMemberCannotReadUnsharedSearchOfColleague(): void
    {
        $ownerToken = $this->loginToken(self::OWNER);
        $this->request('POST', '/v1/searches', $ownerToken, [
            'criteria' => [['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier']],
        ]);
        $privateId = $this->jsonResponse()['search']['id'];

        $memberToken = $this->loginToken(self::MEMBER);
        $this->request('GET', "/v1/searches/{$privateId}", $memberToken);
        // 404 et non 403 : une recherche privée n'existe pas pour les autres, son existence même
        // ne doit pas se déduire.
        self::assertResponseStatusCodeSame(404);
    }

    public function testMemberCannotModifySharedSearchTheyDoNotOwn(): void
    {
        $memberToken = $this->loginToken(self::MEMBER);
        $searchId = $this->demoSearchId();

        $this->request('PATCH', "/v1/searches/{$searchId}", $memberToken, ['name' => 'Détournée']);
        self::assertResponseStatusCodeSame(403, 'Une recherche partagée est en LECTURE SEULE.');

        $this->request('DELETE', "/v1/searches/{$searchId}", $memberToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testMemberCanDuplicateSharedSearchAndOwnsTheCopy(): void
    {
        $memberToken = $this->loginToken(self::MEMBER);
        $searchId = $this->demoSearchId();

        $this->request('POST', "/v1/searches/{$searchId}/duplicate", $memberToken);
        self::assertResponseStatusCodeSame(201);

        $copy = $this->jsonResponse();
        self::assertTrue($copy['isOwn'], 'La copie appartient à celui qui duplique.');
        self::assertFalse($copy['isShared'], "La copie n'est pas partagée d'office.");
        self::assertSame('Leila Mansouri', $copy['ownerName']);
        self::assertNotSame($searchId, $copy['id']);

        // Et il peut, lui, l'éditer.
        $this->request('PATCH', "/v1/searches/{$copy['id']}", $memberToken, ['name' => 'Ma copie']);
        self::assertResponseIsSuccessful();
    }

    public function testSearchOfAnotherOrganizationIsInvisible(): void
    {
        $outsiderToken = $this->loginToken(self::OUTSIDER);
        $searchId = $this->demoSearchId();

        $this->request('GET', "/v1/searches/{$searchId}", $outsiderToken);
        self::assertResponseStatusCodeSame(404);

        $this->request('GET', '/v1/searches', $outsiderToken);
        self::assertSame(0, $this->jsonResponse()['total']);
    }

    public function testNewCountCountsProfilesThatJoinedSinceLastVisitAndResetsOnOpeningResults(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        // L'auteur a « vu » sa recherche à sa création (fixtures rejouées) : on ouvre une fois pour
        // poser un repère franc, puis on simule une entrée postérieure.
        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        self::assertResponseIsSuccessful();

        $this->simulateProfileJoining();

        $this->request('GET', '/v1/searches', $token);
        $mine = $this->itemById($this->jsonResponse()['items'], $searchId);
        self::assertSame(1, $mine['newCount'], 'Un profil a rejoint le jeu de résultats.');

        // Lire la FICHE de la recherche ne consomme pas la nouveauté : ce n'est pas l'avoir vue.
        $this->request('GET', "/v1/searches/{$searchId}", $token);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/v1/searches', $token);
        self::assertSame(
            1,
            $this->itemById($this->jsonResponse()['items'], $searchId)['newCount'],
            'La fiche est une lecture pure, elle ne touche pas au repère.',
        );

        // Ouvrir les RÉSULTATS, si : le badge retombe à 0.
        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/v1/searches', $token);
        $mine = $this->itemById($this->jsonResponse()['items'], $searchId);
        self::assertSame(0, $mine['newCount']);
    }

    public function testTwoRecruitersHaveIndependentNewCountsOnTheSameSharedSearch(): void
    {
        $searchId = $this->demoSearchId();

        // L'auteur ouvre les résultats : son repère est à jour.
        $ownerToken = $this->loginToken(self::OWNER);
        $this->request('GET', "/v1/searches/{$searchId}/results", $ownerToken);

        $this->simulateProfileJoining();

        // L'auteur voit 1 nouveauté, puis l'absorbe en ouvrant.
        $this->request('GET', '/v1/searches', $ownerToken);
        self::assertSame(1, $this->itemById($this->jsonResponse()['items'], $searchId)['newCount']);
        $this->request('GET', "/v1/searches/{$searchId}/results", $ownerToken);

        $this->request('GET', '/v1/searches', $ownerToken);
        self::assertSame(0, $this->itemById($this->jsonResponse()['items'], $searchId)['newCount']);

        // Le membre, lui, n'a rien ouvert : son compteur est intact — il ne dépend pas de l'auteur.
        $memberToken = $this->loginToken(self::MEMBER);
        $this->request('GET', '/v1/searches', $memberToken);
        self::assertGreaterThanOrEqual(
            1,
            $this->itemById($this->jsonResponse()['items'], $searchId)['newCount'],
            'Le repère de lecture est porté par (recherche, recruteur), pas par la recherche.',
        );
    }

    public function testResultsCarryTheWorkStateOfEachCardInTheSearch(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $candidateId = $this->simulateProfileJoining();

        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertGreaterThanOrEqual(1, $payload['total'], 'Le jeu est matérialisé, pas rejoué.');

        $card = $this->itemById($payload['items'], $candidateId);
        self::assertTrue($card['isNew'], 'Le profil a été créé à l’instant par les fixtures.');
        self::assertTrue($card['isFavorite'], 'Imane Tazi est favorite dans les fixtures.');
    }

    /**
     * Le défaut qui a motivé `API-022` : servir les résultats remet le repère de lecture à jour, et
     * l'ancienne règle éteignait donc les badges dès la requête suivante — sans qu'aucun profil ait
     * été regardé.
     *
     * Un état qui disparaît quand on recharge n'est pas un état. Depuis, « nouveau » est un ÂGE.
     */
    public function testNewnessSurvivesAReload(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $candidateId = $this->simulateProfileJoining();

        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        $first = $this->itemById($this->jsonResponse()['items'], $candidateId)['isNew'];

        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        $second = $this->itemById($this->jsonResponse()['items'], $candidateId)['isNew'];

        self::assertTrue($first, 'Le profil est récent : il est nouveau au premier appel.');
        self::assertSame($first, $second, 'Relire le jeu ne consomme pas la nouveauté d’un profil.');
    }

    /**
     * Le seuil est ABSOLU et partagé : il ne dépend ni du recruteur, ni de ce qu'il a ouvert.
     *
     * Ici on vieillit le profil en base plutôt que d'attendre huit jours — c'est la même bascule que
     * couvre `ProfileFreshnessTest`, vérifiée cette fois de bout en bout, sur la charge utile réelle.
     */
    public function testAProfileOlderThanAWeekIsNoLongerNew(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $candidateId = $this->simulateProfileJoining();

        // En SQL et non par l'entité : `createdAt` n'a pas de setter, et lui en ouvrir un pour les
        // besoins d'un test rendrait modifiable une date qui ne doit jamais l'être.
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement(
            'UPDATE candidate SET created_at = :date WHERE uuid = :uuid',
            ['date' => (new DateTimeImmutable('-8 days'))->format('Y-m-d H:i:s'), 'uuid' => $candidateId],
        );
        $em->clear();

        $this->request('GET', "/v1/searches/{$searchId}/results", $token);

        $card = $this->itemById($this->jsonResponse()['items'], $candidateId);
        self::assertFalse($card['isNew'], 'Huit jours : le profil a cessé d’être nouveau.');
        self::assertTrue($card['isFavorite'], 'Et son état de travail, lui, n’a pas bougé.');
    }

    public function testEngineResultsCarryNoWorkStateAtAll(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('GET', '/v1/search/candidates?perPage=1', $token);
        self::assertResponseIsSuccessful();

        $card = $this->jsonResponse()['items'][0] ?? null;
        self::assertIsArray($card);
        // Hors d'une recherche, la question ne se pose pas : `null` et non `false`.
        self::assertNull($card['isNew']);
        self::assertNull($card['isFavorite']);
    }

    public function testSearchCardAnnouncesTheSizeOfItsResultSet(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $this->simulateProfileJoining();

        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        $total = $this->jsonResponse()['total'];

        $this->request('GET', '/v1/searches', $token);
        self::assertSame(
            $total,
            $this->itemById($this->jsonResponse()['items'], $searchId)['resultCount'],
            'La carte annonce exactement le jeu que la Table servira.',
        );
    }

    public function testWritingASearchReturnsItsResultSetInTheSameResponse(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier']],
        ]);
        self::assertResponseStatusCodeSame(201);

        $payload = $this->jsonResponse();
        self::assertArrayHasKey('search', $payload);
        self::assertArrayHasKey('results', $payload);

        $results = $payload['results'];
        self::assertSame(
            $payload['search']['resultCount'],
            $results['total'],
            'La fiche et le jeu de la même réponse annoncent le même nombre.',
        );
        self::assertCount(
            $results['total'],
            $results['resultIds'],
            'Les identifiants portent TOUT le jeu, pas seulement la page servie.',
        );

        // La nouveauté d'un profil est son ÂGE, pas la fraîcheur de la recherche : composer une
        // recherche neuve ne rend donc rien nouveau, et n'éteint rien non plus. Les profils des
        // fixtures viennent d'être créés, ils sont récents — cette assertion disait l'inverse tant
        // que « nouveau » voulait dire « depuis ta dernière visite » (`API-022`).
        foreach ($results['items'] as $card) {
            self::assertTrue($card['isNew'], 'Les profils des fixtures ont moins de sept jours.');
        }

        // Ajuster les critères rend le NOUVEAU jeu dans la même réponse : c'est tout l'intérêt.
        $searchId = $payload['search']['id'];
        $this->request('PATCH', "/v1/searches/{$searchId}", $token, [
            'criteria' => [['facet' => 'sector', 'value' => 'Informatique', 'group' => 'metier']],
        ]);
        self::assertResponseIsSuccessful();

        $patched = $this->jsonResponse();
        self::assertSame($patched['search']['resultCount'], $patched['results']['total']);
        self::assertCount($patched['results']['total'], $patched['results']['resultIds']);
    }

    public function testResultsCarryEveryIdentifierOfTheSetEvenWhenPaginated(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $this->simulateProfileJoining();

        $this->request('GET', "/v1/searches/{$searchId}/results?perPage=1", $token);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertGreaterThan(1, $payload['total'], 'Le jeu déborde de la page demandée.');
        self::assertCount(1, $payload['items'], 'La page, elle, reste bornée à ce qui est demandé.');
        self::assertCount($payload['total'], $payload['resultIds'], 'La liste ignore la pagination.');

        // Le même ordre des deux côtés : sinon un rang dans la liste ne désignerait pas la personne
        // de la carte du même rang, et toute la pagination du client serait décalée.
        self::assertSame($payload['items'][0]['id'], $payload['resultIds'][0]);
    }

    /**
     * Chaque carte dit POURQUOI elle est là : un état par critère de la recherche, et le libellé du
     * profil qui le satisfait.
     *
     * Tous les critères sont satisfaits tant qu'ils se cumulent en ET — c'est le cas aujourd'hui, et
     * c'est justement ce qui rendait l'explication invisible. Elle prend tout son sens quand les
     * mots-clés se relient en OU : un profil entre alors en n'en satisfaisant qu'un, et il faut
     * pouvoir dire lequel.
     */
    public function testResultsExplainWhichCriteriaEachProfileSatisfies(): void
    {
        $token = $this->loginToken(self::OWNER);
        $this->request('GET', "/v1/searches/{$this->demoSearchId()}/results", $token);
        self::assertResponseIsSuccessful();

        $card = $this->jsonResponse()['items'][0] ?? null;
        self::assertIsArray($card);

        $matches = $card['matches'];
        // La recherche de démonstration porte trois critères : secteur, disponibilité, séjour.
        self::assertCount(3, $matches);
        self::assertSame(['sector', 'availability', 'presenceCity'], array_column($matches, 'facet'));

        foreach ($matches as $match) {
            self::assertTrue($match['matched'], 'un membre du jeu satisfait tous les critères en ET');
            self::assertNotNull($match['matchedOn'], 'ce qui, DANS le profil, satisfait le critère');
        }
    }

    public function testEngineResultsCarryNoCriteriaExplanationAtAll(): void
    {
        $token = $this->loginToken(self::OWNER);
        $this->request('GET', '/v1/search/candidates?perPage=1', $token);
        self::assertResponseIsSuccessful();

        // Hors d'une recherche il n'y a pas de critères posés : la question n'a pas d'objet.
        self::assertNull($this->jsonResponse()['items'][0]['matches'] ?? null);
    }

    public function testResultsOfASearchOneCannotReadAreNotFound(): void
    {
        $searchId = $this->demoSearchId();

        $this->request('GET', "/v1/searches/{$searchId}/results", $this->loginToken(self::OUTSIDER));
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Matérialise l'entrée d'un candidat dans le jeu de résultats, postérieurement au repère de
     * lecture — c'est exactement ce que produit une ingestion Jarvi.
     *
     * Les repères existants sont ANTIDATÉS plutôt que l'entrée postdatée : Postgres stocke ces
     * horodatages à la seconde, et une entrée créée dans la même seconde qu'une ouverture ne serait
     * pas comptée. On écarte les deux instants au lieu de parier sur l'horloge.
     *
     * @return string uuid public du candidat entrant, pour le retrouver dans le jeu servi
     */
    private function simulateProfileJoining(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy([]);
        self::assertInstanceOf(SavedSearch::class, $search);

        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'Tazi']);
        self::assertInstanceOf(Person::class, $person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertInstanceOf(Candidate::class, $candidate);

        foreach ($em->getRepository(SearchView::class)->findBy(['search' => $search]) as $view) {
            $view->lastViewedAt = new DateTimeImmutable('-1 hour');
        }

        $existing = $em->getRepository(SearchResultMembership::class)
            ->findOneBy(['search' => $search, 'candidate' => $candidate]);
        if (null !== $existing) {
            $em->remove($existing);
            $em->flush();
        }

        $em->persist(new SearchResultMembership($search, $candidate, new DateTimeImmutable()));
        $em->flush();

        return $candidate->getUuid()->toString();
    }

    private function demoSearchId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);

        return $search->getUuid()->toString();
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function itemById(array $items, string $id): array
    {
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        self::fail(\sprintf('Recherche %s absente de la liste.', $id));
    }
}
