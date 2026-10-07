<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Favorite;
use App\Entity\Organization;
use App\Entity\ProfileView;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Entity\SavedSearch;
use App\Enum\RevealType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Activité candidat (tâche 019-A) : compteurs agrégés (vues, déblocages, entreprises intéressées),
 * lecture self-only. Aucune identité d'entreprise n'est exposée (anonymisation côté serveur).
 */
final class CandidateActivityTest extends ApiTestCase
{
    public function testCandidateSeesOwnActivityCounters(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('activity.self@example.com');
        $this->seedActivity($candidateId, views: 3, reveals: 1, favorites: 1);

        $this->request('GET', "/v1/candidates/{$candidateId}/activity", $token);
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        self::assertSame(3, $body['profileViews']);
        self::assertSame(1, $body['reveals']);
        self::assertSame(1, $body['interestedCompanies']);
        // Compteurs seuls : aucune identité d'entreprise ne doit fuiter.
        self::assertArrayNotHasKey('organization', $body);
        self::assertArrayNotHasKey('organizations', $body);
    }

    public function testActivityIsZeroForBrandNewCandidate(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('activity.zero@example.com');

        $this->request('GET', "/v1/candidates/{$candidateId}/activity", $token);
        self::assertResponseIsSuccessful();

        self::assertSame(
            ['profileViews' => 0, 'reveals' => 0, 'interestedCompanies' => 0],
            $this->jsonResponse(),
        );
    }

    public function testCannotReadAnotherCandidatesActivity(): void
    {
        [, $candidateA] = $this->onboardAndLogin('activity.a@example.com');
        [$tokenB] = $this->onboardAndLogin('activity.b@example.com');

        $this->request('GET', "/v1/candidates/{$candidateA}/activity", $tokenB);
        self::assertResponseStatusCodeSame(403);
    }

    public function testUnknownCandidateReturns404(): void
    {
        [$token] = $this->onboardAndLogin('activity.404@example.com');

        $this->request('GET', '/v1/candidates/01900000-0000-7000-8000-0000000000ff/activity', $token);
        self::assertResponseStatusCodeSame(404);
    }

    private function seedActivity(string $candidateUuid, int $views, int $reveals, int $favorites): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['uuid' => $candidateUuid]);
        self::assertInstanceOf(Candidate::class, $candidate);
        $organization = $em->getRepository(Organization::class)->findOneBy([]);
        self::assertInstanceOf(Organization::class, $organization);
        $recruiter = $em->getRepository(Recruiter::class)->findOneBy([]);
        self::assertInstanceOf(Recruiter::class, $recruiter);

        for ($i = 0; $i < $views; ++$i) {
            $em->persist(new ProfileView($candidate, $organization, $recruiter));
        }
        for ($i = 0; $i < $reveals; ++$i) {
            $em->persist(new RevealedProfile($organization, $candidate, $recruiter, RevealType::EMAIL));
        }
        // Depuis la task 073, un favori pend à une recherche : il faut donc une recherche pour en
        // poser un. `interestedCompanies` compte les entreprises distinctes, pas les lignes.
        if ($favorites > 0) {
            $search = new SavedSearch($recruiter, $organization, 'Recherche activité', [
                ['facet' => 'sector', 'value' => 'Informatique', 'group' => 'metier'],
            ]);
            $em->persist($search);

            for ($i = 0; $i < $favorites; ++$i) {
                $em->persist(new Favorite($search, $candidate, $recruiter));
            }
        }
        $em->flush();
    }

    /**
     * @return array{0: string, 1: string} [token, candidateId]
     */
    private function onboardAndLogin(string $email): array
    {
        $this->request('POST', '/v1/candidates/onboarding', null, [
            'email' => $email,
            'password' => 'password123',
            'givenName' => 'Test',
            'familyName' => 'Candidat',
            'linkedinUrl' => 'https://www.linkedin.com/in/test-candidat',
        ]);
        self::assertResponseStatusCodeSame(201);
        $candidateId = (string) $this->jsonResponse()['candidateId'];

        return [$this->loginToken($email, 'password123'), $candidateId];
    }
}
