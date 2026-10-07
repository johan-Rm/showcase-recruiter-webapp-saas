<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * L'avancement d'un recruteur dans le tri d'une recherche : vu, écarté, remis (task API-007).
 *
 * Fixtures : « Finance France vers Casablanca » appartient à atlas.owner et est PARTAGÉE ;
 * atlas.member est de la même organisation et ne peut pas l'écrire.
 */
final class SearchProfileReviewTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';

    public function testDismissingRemovesTheProfileFromThePileAndRestoringBringsItBack(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();

        $before = $this->results($searchId, $token);
        self::assertNotEmpty($before['resultIds']);
        $candidateId = $before['resultIds'][0];
        $total = $before['total'];

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => [$candidateId],
            'action' => 'DISMISS',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['dismissedCount']);
        // Écarter implique d'avoir regardé : on ne rejette pas ce qu'on n'a pas vu.
        self::assertSame(1, $this->jsonResponse()['seenCount']);

        $after = $this->results($searchId, $token);
        self::assertNotContains($candidateId, $after['resultIds'], 'l’écarté quitte la pile');
        self::assertSame($total - 1, $after['total'], 'le total suit la pile, il ne la contredit pas');

        // Il n'a pas disparu : la corbeille le rend, et c'est ce qui permet d'oser écarter.
        $bin = $this->results($searchId, $token, '&scope=dismissed');
        self::assertSame([$candidateId], $bin['resultIds']);
        self::assertTrue($bin['items'][0]['isDismissed']);
        self::assertTrue($bin['items'][0]['isSeen']);

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => [$candidateId],
            'action' => 'RESTORE',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->jsonResponse()['dismissedCount']);
        // Remettre efface l'écart, jamais le fait d'avoir vu.
        self::assertSame(1, $this->jsonResponse()['seenCount']);

        self::assertContains($candidateId, $this->results($searchId, $token)['resultIds']);
    }

    public function testTheStateIsPersonalAndNotSharedWithColleagues(): void
    {
        $ownerToken = $this->loginToken(self::OWNER);
        $searchId = $this->demoSearchId();
        $candidateId = $this->results($searchId, $ownerToken)['resultIds'][0];

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $ownerToken, [
            'candidateIds' => [$candidateId],
            'action' => 'DISMISS',
        ]);
        self::assertResponseIsSuccessful();

        // Le collègue voit toujours le profil : écarter est SOUSTRACTIF, le partager priverait
        // quelqu'un d'un profil qu'il n'a jamais choisi d'ignorer.
        $memberToken = $this->loginToken(self::MEMBER);
        $forMember = $this->results($searchId, $memberToken);
        self::assertContains($candidateId, $forMember['resultIds']);
        self::assertFalse($forMember['items'][0]['isDismissed'] ?? false);
    }

    public function testTheRecipientOfASharedSearchCanStillTriageIt(): void
    {
        $memberToken = $this->loginToken(self::MEMBER);
        $searchId = $this->demoSearchId();
        $candidateId = $this->results($searchId, $memberToken)['resultIds'][0];

        // Il ne peut ni modifier la recherche ni la favoriser — mais il doit pouvoir la trier,
        // sinon le partage ne sert à rien : il repasserait le jeu entier sans noter son avancement.
        $this->request('POST', "/v1/searches/{$searchId}/reviews", $memberToken, [
            'candidateIds' => [$candidateId],
            'action' => 'SEEN',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['seenCount']);
    }

    public function testAProfileOutsideTheSetIsRefusedByName(): void
    {
        $token = $this->loginToken(self::OWNER);
        $unknown = '01900000-0000-7000-8000-00000000ffff';

        $this->request('POST', "/v1/searches/{$this->demoSearchId()}/reviews", $token, [
            'candidateIds' => [$unknown],
            'action' => 'SEEN',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString($unknown, (string) $this->client->getResponse()->getContent());
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
