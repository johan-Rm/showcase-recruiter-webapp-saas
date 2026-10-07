<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * L'état de travail d'une recherche, servi avec son jeu — retenus, écartés, vus (task `API-047`).
 *
 * Ce que ces listes réparent : l'API ne savait dire cet état que **carte par carte**
 * (`isFavorite`, `isDismissed`, `isSeen`), donc page par page. Un profil retenu au-delà des cartes
 * chargées était compté sans être connu, et trois appels de rattrapage partaient à chaque ouverture
 * pour combler ce trou.
 *
 * Elles ne voyagent qu'à **l'ouverture** — première page de la pile — et sont **absentes** ailleurs,
 * jamais vides : un `favoriteIds: []` en page 2 signifierait « aucun retenu », ce qui serait faux.
 *
 * Fixtures : « Finance France vers Casablanca » est **partagée**, matérialise deux profils et les
 * retient tous les deux. Le partage est ce qui permet de vérifier la règle la plus subtile — les
 * retenus appartiennent à la RECHERCHE, l'avancement appartient au LECTEUR.
 */
final class SearchWorkStateTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';

    /** Les quatre listes de l'ouverture, dans l'ordre où la réponse les porte. */
    private const array LISTS = ['resultIds', 'favoriteIds', 'dismissedIds', 'seenIds'];

    public function testOpeningCarriesTheWorkStateOfTheWholeSearch(): void
    {
        $token = $this->loginToken(self::OWNER);
        $page = $this->results($this->demoSearchId(), $token);

        foreach (self::LISTS as $list) {
            self::assertArrayHasKey($list, $page, "l'ouverture porte {$list}");
        }

        self::assertEqualsCanonicalizing(
            $page['resultIds'],
            $page['favoriteIds'],
            'les fixtures retiennent tout le jeu',
        );
        self::assertSame([], $page['dismissedIds'], 'rien n\'a été écarté');
    }

    /**
     * Au-delà de la première page, les listes sont ABSENTES.
     *
     * Absentes et non vides : le client demande toujours la page 1 en ouvrant, il a donc déjà l'état
     * — le répéter serait du poids, mais le servir vide serait un mensonge.
     */
    public function testTheListsDoNotTravelBeyondTheFirstPage(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $this->request('GET', "/v1/searches/{$searchId}/results?page=2", $token);
        self::assertResponseIsSuccessful();

        $page = $this->jsonResponse();
        foreach (self::LISTS as $list) {
            self::assertArrayNotHasKey($list, $page, "{$list} ne repart pas en page 2");
        }
    }

    /**
     * Les périmètres ne portent QUE leur `resultIds`.
     *
     * Ils sont demandés **après** l'ouverture, quand le client connaît déjà l'état de la recherche.
     * Leur `resultIds`, lui, reste indispensable : c'est lui qui peint l'onglet « Favoris » depuis
     * `ENT-015`.
     */
    public function testThePerimetersCarryTheirSetButNotTheWorkState(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        foreach (['favorites', 'dismissed'] as $scope) {
            $this->request('GET', "/v1/searches/{$searchId}/results?scope={$scope}", $token);
            self::assertResponseIsSuccessful();

            $page = $this->jsonResponse();
            self::assertArrayHasKey('resultIds', $page, "?scope={$scope} garde son jeu");
            self::assertArrayNotHasKey('favoriteIds', $page);
            self::assertArrayNotHasKey('dismissedIds', $page);
            self::assertArrayNotHasKey('seenIds', $page);
        }
    }

    /**
     * Écarter un profil retenu le sort des retenus — la liste s'aligne sur `?scope=favorites`, pas
     * sur l'ancienne route des favoris.
     *
     * C'est exactement l'écart qui avait coûté un compteur faux (`ENT-015`) : deux sources qui
     * répondent à la même question finissent par diverger, ici d'un profil écarté.
     */
    public function testARetainedProfileLeavesTheRetainedListOnceDismissed(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $before = $this->results($searchId, $token);
        $dismissed = $before['favoriteIds'][0];

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => [$dismissed],
            'action' => 'DISMISS',
        ]);
        self::assertResponseIsSuccessful();

        $after = $this->results($searchId, $token);
        self::assertNotContains($dismissed, $after['favoriteIds'], 'les retenus déduisent les écartés');
        self::assertContains($dismissed, $after['dismissedIds'], 'et la corbeille le porte');
        self::assertNotContains($dismissed, $after['resultIds'], 'il a quitté la pile');
    }

    /**
     * Les retenus appartiennent à la RECHERCHE, l'avancement au LECTEUR.
     *
     * Sur une recherche partagée, le destinataire voit donc les mêmes retenus que l'auteur, mais son
     * propre « vu ». Confondre les deux ferait apparaître comme déjà parcourus des profils que ce
     * lecteur-là n'a jamais ouverts.
     */
    public function testTheReaderSeesTheSearchRetainedButHisOwnProgress(): void
    {
        $searchId = $this->demoSearchId();
        $ownerToken = $this->loginToken(self::OWNER);
        $seen = $this->results($searchId, $ownerToken)['resultIds'][0];

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $ownerToken, [
            'candidateIds' => [$seen],
            'action' => 'SEEN',
        ]);
        self::assertResponseIsSuccessful();

        $owner = $this->results($searchId, $ownerToken);
        self::assertContains($seen, $owner['seenIds'], 'l\'auteur a bien vu ce profil');

        $member = $this->results($searchId, $this->loginToken(self::MEMBER));
        self::assertSame([], $member['seenIds'], 'le collègue n\'a rien vu, lui');
        self::assertEqualsCanonicalizing(
            $owner['favoriteIds'],
            $member['favoriteIds'],
            'mais les retenus sont ceux de la recherche, partagés',
        );
    }

    /** @return array<string, mixed> */
    private function results(string $searchId, string $token): array
    {
        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        self::assertResponseIsSuccessful();

        return $this->jsonResponse();
    }

    private function demoSearchId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);

        return $search->getUuid()->toString();
    }
}
