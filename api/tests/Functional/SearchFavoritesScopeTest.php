<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le jeu de résultats réduit aux profils retenus — `?scope=favorites` (task API-014).
 *
 * Le client connaissait les identifiants de ses retenus mais pas leurs cartes : le jeu arrive par
 * pages de trente, et un favori posé au-delà de la première n'apparaissait nulle part. L'onglet
 * « Favoris » comptait donc ce qui était chargé, pas ce qui était retenu.
 *
 * Fixtures : « Finance France vers Casablanca » matérialise deux profils et les retient **tous les
 * deux**. Les tests procèdent donc par RETRAIT — c'est aussi la meilleure preuve que le périmètre est
 * la récolte et non la pile, puisque les deux coïncident au départ.
 */
final class SearchFavoritesScopeTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';

    public function testTheScopeFollowsTheRetainedListAndNotThePile(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $pile = $this->results($searchId, $token)['resultIds'];
        $retained = $this->results($searchId, $token, '&scope=favorites');
        self::assertEqualsCanonicalizing($pile, $retained['resultIds'], 'les fixtures retiennent tout le jeu');
        self::assertSame(\count($retained['resultIds']), $retained['total'], 'le total porte sur le sous-ensemble');

        $released = $pile[0];
        $this->request('DELETE', "/v1/searches/{$searchId}/favorites/{$released}", $token);
        self::assertResponseIsSuccessful();

        $after = $this->results($searchId, $token, '&scope=favorites');
        self::assertNotContains($released, $after['resultIds'], 'ne plus retenir sort de la récolte');
        self::assertSame($retained['total'] - 1, $after['total']);
        // La pile, elle, ne bouge pas : ne plus retenir n'écarte pas.
        self::assertEqualsCanonicalizing($pile, $this->results($searchId, $token)['resultIds']);

        foreach ($after['items'] as $card) {
            self::assertTrue($card['isFavorite'], 'le périmètre ne sert QUE des retenus');
        }
    }

    /**
     * « Favoris » reste un SOUS-ENSEMBLE de la pile.
     *
     * Sans cette déduction, les deux onglets de « Ma sélection » annonceraient des comptes qui ne se
     * recouvrent pas — un profil écarté continuerait d'être compté parmi les retenus alors que la
     * pile ne le montre plus.
     */
    public function testADismissedProfileLeavesTheRetainedScope(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $before = $this->results($searchId, $token, '&scope=favorites')['resultIds'];
        self::assertGreaterThanOrEqual(2, \count($before), 'fixtures trop maigres pour ce test');
        $dismissed = $before[0];

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => [$dismissed],
            'action' => 'DISMISS',
        ]);
        self::assertResponseIsSuccessful();

        $after = $this->results($searchId, $token, '&scope=favorites')['resultIds'];
        self::assertNotContains($dismissed, $after, 'l’écarté quitte la récolte');
        self::assertCount(\count($before) - 1, $after, 'les autres retenus y restent');

        // Il n'a pas cessé d'être retenu pour autant : la corbeille le rend, favori compris.
        $bin = $this->results($searchId, $token, '&scope=dismissed');
        self::assertSame([$dismissed], $bin['resultIds']);
        self::assertTrue($bin['items'][0]['isFavorite']);
    }

    /** Une valeur inconnue ne casse pas une adresse partagée : elle retombe sur la pile de travail. */
    public function testAnUnknownScopeFallsBackToTheWorkingPile(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        self::assertSame(
            $this->results($searchId, $token)['resultIds'],
            $this->results($searchId, $token, '&scope=nimportequoi')['resultIds'],
        );
    }

    /** @return array<string, mixed> */
    private function results(string $searchId, string $token, string $query = ''): array
    {
        $this->request('GET', "/v1/searches/{$searchId}/results?perPage=200{$query}", $token);
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
