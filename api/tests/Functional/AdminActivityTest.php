<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Organization;
use App\Entity\ProfileView;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Enum\RevealType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * GET /v1/admin/activity (tâche API-073) : le fil des visites de profil, toutes organisations
 * confondues. Lecture seule, gardée ROLE_ADMIN. C'est la mention « dévoilé » qui porte la valeur du
 * fil : un candidat souvent regardé et jamais débloqué n'a pas la même lecture qu'un autre.
 */
final class AdminActivityTest extends ApiTestCase
{
    private const string ADMIN_EMAIL = 'admin@demo.showcase-recruiter-webapp-saas.example';
    private const string OWNER_EMAIL = 'owner@demo.showcase-recruiter-webapp-saas.example';
    private const string ATLAS_OWNER_EMAIL = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string URI = '/v1/admin/activity';

    public function testFeedListsVisitsMostRecentFirst(): void
    {
        $this->visitAs(self::OWNER_EMAIL);
        $this->visitAs(self::ATLAS_OWNER_EMAIL);

        $this->request('GET', self::URI, $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        self::assertGreaterThanOrEqual(2, $body['total']);
        self::assertSame(1, $body['page']);

        $dates = array_column($body['items'], 'viewedAt');
        $sorted = $dates;
        rsort($sorted);
        self::assertSame($sorted, $dates, 'Le fil doit aller du plus récent au plus ancien.');
    }

    public function testEachLineSaysWhoLookedAtWhom(): void
    {
        $candidateId = $this->visitAs(self::OWNER_EMAIL);

        $this->request('GET', self::URI, $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();

        $line = $this->lineFor($candidateId, 'showcase-recruiter-webapp-saas Demo SARL');
        self::assertSame('Sara Bennani', $line['recruiterName']);
        self::assertSame(self::OWNER_EMAIL, $line['recruiterEmail']);
        self::assertSame($candidateId, $line['candidateId']);
        // Nom complet : l'administration voit les profils non masqués.
        self::assertStringContainsString(' ', trim($line['candidateName']));
        self::assertArrayHasKey('revealed', $line);
    }

    /** Deux entreprises sur le MÊME candidat : celle qui a payé se distingue de celle qui regarde. */
    public function testRevealedTellsWhoUnlockedTheProfile(): void
    {
        $candidateId = $this->visitAs(self::OWNER_EMAIL);
        self::assertSame($candidateId, $this->visitAs(self::ATLAS_OWNER_EMAIL));
        // Le dévoilement est posé ici plutôt que lu dans les fixtures : lequel des profils de démo
        // est débloqué n'a pas à décider du sort de ce test.
        $this->reveal($candidateId, 'Atlas Talent Conseil');

        $this->request('GET', self::URI, $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();

        self::assertTrue($this->lineFor($candidateId, 'Atlas Talent Conseil')['revealed']);
        self::assertFalse($this->lineFor($candidateId, 'showcase-recruiter-webapp-saas Demo SARL')['revealed']);
    }

    public function testPaginationAndPerPageCap(): void
    {
        $this->visitAs(self::OWNER_EMAIL);
        $this->visitAs(self::ATLAS_OWNER_EMAIL);
        $token = $this->loginToken(self::ADMIN_EMAIL);

        $this->request('GET', self::URI.'?page=1&perPage=1', $token);
        self::assertResponseIsSuccessful();
        $first = $this->jsonResponse();
        self::assertCount(1, $first['items']);
        self::assertSame(1, $first['perPage']);

        $this->request('GET', self::URI.'?page=2&perPage=1', $token);
        self::assertResponseIsSuccessful();
        $second = $this->jsonResponse();
        self::assertCount(1, $second['items']);
        self::assertNotSame($first['items'][0]['id'], $second['items'][0]['id']);

        $this->request('GET', self::URI.'?perPage=999', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(100, $this->jsonResponse()['perPage']);
    }

    public function testEmptyFeedIsAListNotAnError(): void
    {
        $this->entityManager()->createQuery('DELETE FROM '.ProfileView::class.' v')->execute();

        $this->request('GET', self::URI, $this->loginToken(self::ADMIN_EMAIL));

        self::assertResponseIsSuccessful();
        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'perPage' => 20], $this->jsonResponse());
    }

    /** Une trace historique ne disparaît pas parce qu'un compte a été fermé. */
    public function testAClosedRecruiterAccountKeepsItsTrace(): void
    {
        $candidateId = $this->visitAs(self::OWNER_EMAIL);
        $this->request('GET', self::URI, $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();
        $before = $this->jsonResponse()['total'];

        $this->closeRecruiterAccountOf(self::OWNER_EMAIL);

        $this->request('GET', self::URI, $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();
        $body = $this->jsonResponse();
        self::assertSame($before, $body['total']);
        // `lineFor` échoue si la ligne manque : ce qui se vérifie ici, c'est qu'elle porte encore
        // le nom du recruteur dont le compte vient d'être fermé.
        self::assertSame('Sara Bennani', $this->lineFor($candidateId, 'showcase-recruiter-webapp-saas Demo SARL', $body)['recruiterName']);
    }

    public function testARecruiterCannotReadTheFeed(): void
    {
        $this->request('GET', self::URI, $this->loginToken(self::OWNER_EMAIL));
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnAnonymousCallerCannotReadTheFeed(): void
    {
        $this->request('GET', self::URI);
        self::assertResponseStatusCodeSame(401);
    }

    /** Ouvre une fiche candidat en tant que recruteur — la visite naît du geste réel (API-072). */
    private function visitAs(string $email): string
    {
        $token = $this->loginToken($email);
        $this->request('GET', '/v1/search/candidates', $token);
        self::assertResponseIsSuccessful();

        $items = $this->jsonResponse()['items'];
        self::assertNotEmpty($items);
        $candidateId = $items[0]['id'];

        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseIsSuccessful();

        return $candidateId;
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    private function lineFor(string $candidateId, string $organizationName, ?array $body = null): array
    {
        $items = ($body ?? $this->jsonResponse())['items'];
        foreach ($items as $line) {
            if ($line['candidateId'] === $candidateId && $line['organizationName'] === $organizationName) {
                return $line;
            }
        }

        self::fail("Aucune ligne pour {$organizationName} sur le candidat {$candidateId}.");
    }

    /** Pose un dévoilement de cette organisation sur ce candidat, sans passer par les crédits. */
    private function reveal(string $candidateId, string $organizationName): void
    {
        $em = $this->entityManager();

        $candidate = $em->getRepository(Candidate::class)->findOneBy(['uuid' => $candidateId]);
        self::assertInstanceOf(Candidate::class, $candidate);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => $organizationName]);
        self::assertInstanceOf(Organization::class, $organization);
        $recruiter = $em->getRepository(Recruiter::class)->findOneBy(['organization' => $organization]);
        self::assertInstanceOf(Recruiter::class, $recruiter);

        $em->persist(new RevealedProfile($organization, $candidate, $recruiter, RevealType::EMAIL));
        $em->flush();
    }

    private function closeRecruiterAccountOf(string $email): void
    {
        $em = $this->entityManager();
        $recruiter = $em->createQuery(
            'SELECT r FROM '.Recruiter::class.' r JOIN r.person p WHERE p.contactEmail = :email'
        )
            ->setParameter('email', $email)
            ->getSingleResult();
        self::assertInstanceOf(Recruiter::class, $recruiter);

        $recruiter->softDelete();
        $em->flush();
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
