<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Person;
use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Favoris rattachés à une recherche (tâche 073, réduite par la 151 : la shortlist est retirée du
 * produit — il n'y a plus qu'une liste de rétention, et elle accepte les profils masqués).
 *
 * Fixtures Atlas : Tazi est DÉVOILÉE pour l'organisation, El Amrani ne l'est pas. La recherche
 * « Finance France vers Casablanca » appartient à atlas.owner et est partagée avec atlas.member.
 */
final class SearchListsTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';

    public function testFavoriteWorksOnAMaskedProfile(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->freshSearchId($token);

        // El Amrani n'est pas dévoilé : le favori doit passer quand même — c'est le filet large.
        $this->request('POST', "/v1/searches/{$searchId}/favorites", $token, [
            'candidateId' => $this->candidateId('El Amrani'),
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->request('GET', "/v1/searches/{$searchId}/favorites", $token);
        self::assertSame(1, $this->jsonResponse()['total']);
    }

    public function testListsAreScopedToTheirSearch(): void
    {
        $token = $this->loginToken(self::OWNER);
        $first = $this->freshSearchId($token);
        $second = $this->freshSearchId($token);
        $tazi = $this->candidateId('Tazi');

        $this->request('POST', "/v1/searches/{$first}/favorites", $token, ['candidateId' => $tazi]);
        self::assertResponseIsSuccessful();

        $this->request('GET', "/v1/searches/{$second}/favorites", $token);
        self::assertSame(0, $this->jsonResponse()['total'], 'Deux recherches ont des favoris distincts.');
    }

    public function testNonAuthorCannotWriteInListsOfASharedSearch(): void
    {
        $memberToken = $this->loginToken(self::MEMBER);
        $searchId = $this->sharedSearchId();
        $tazi = $this->candidateId('Tazi');

        $this->request('POST', "/v1/searches/{$searchId}/favorites", $memberToken, ['candidateId' => $tazi]);
        self::assertResponseStatusCodeSame(403);

        // Il peut en revanche les LIRE : une recherche partagée est en lecture seule, pas invisible.
        $this->request('GET', "/v1/searches/{$searchId}/favorites", $memberToken);
        self::assertResponseIsSuccessful();
    }

    public function testCannotFavoriteCandidateWithoutConsent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidate('El Amrani');
        $candidate->withdrawConsent();
        $em->flush();

        $token = $this->loginToken(self::OWNER);
        $searchId = $this->freshSearchId($token);

        $this->request('POST', "/v1/searches/{$searchId}/favorites", $token, [
            'candidateId' => $candidate->getUuid()->toString(),
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    /** Une recherche neuve, vierge de toute liste — l'auteur est le recruteur connecté. */
    private function freshSearchId(string $token): string
    {
        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier']],
        ]);
        self::assertResponseStatusCodeSame(201);

        // L'écriture renvoie la recherche ET son jeu : la fiche est sous `search`.
        return $this->jsonResponse()['search']['id'];
    }

    private function sharedSearchId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);

        return $search->getUuid()->toString();
    }

    private function candidateId(string $familyName): string
    {
        return $this->candidate($familyName)->getUuid()->toString();
    }

    private function candidate(string $familyName): Candidate
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => $familyName]);
        self::assertInstanceOf(Person::class, $person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertInstanceOf(Candidate::class, $candidate);

        return $candidate;
    }
}
