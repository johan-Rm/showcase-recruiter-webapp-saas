<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Candidate\CandidateOnboarder;
use App\Enum\SyncStatus;

/**
 * Onboarding candidat (tâche 015) : inscription publique, contexte « Me », auth, doublon, validation.
 */
final class CandidateOnboardingTest extends ApiTestCase
{
    private const string ENDPOINT = '/v1/candidates/onboarding';

    public function testOnboardingCreatesCandidateAccount(): void
    {
        $this->request('POST', self::ENDPOINT, null, [
            'email' => 'nadia.candidate@example.com',
            'password' => 'password123',
            'givenName' => 'Nadia',
            'familyName' => 'Berrada',
            'linkedinUrl' => 'https://www.linkedin.com/in/nadia-berrada',
            'country' => 'FR',
            'city' => 'Lyon',
        ]);

        self::assertResponseStatusCodeSame(201);
        $payload = $this->jsonResponse();
        self::assertTrue($payload['candidate']);
        self::assertFalse($payload['recruiter']);
        self::assertNotNull($payload['candidateId']);
        self::assertContains('ROLE_CANDIDATE', $payload['roles']);
    }

    public function testOnboardingAttachesEmptyJarviReplica(): void
    {
        // Invariant : toute projection candidat porte son conteneur de réplica pro (CandidateJarvi),
        // vide/PENDING tant que l'ingestion Jarvi n'a pas tourné (tâche 020). Le mapping de sortie
        // s'appuie sur cette présence (accès non-null à getJarvi()).
        $onboarder = self::getContainer()->get(CandidateOnboarder::class);
        $candidate = $onboarder->onboard(
            'replica.candidate@example.com',
            'password123',
            'Réplica',
            'Test',
            'https://www.linkedin.com/in/replica-test',
        );

        $jarvi = $candidate->getJarvi();
        self::assertNotNull($jarvi);
        self::assertSame(SyncStatus::PENDING, $jarvi->syncStatus);
        self::assertNull($jarvi->headline);
        self::assertSame([], $jarvi->skills);
    }

    public function testCandidateCanAuthenticateAndSeeContext(): void
    {
        $this->request('POST', self::ENDPOINT, null, [
            'email' => 'sami.candidate@example.com',
            'password' => 'password123',
            'givenName' => 'Sami',
            'familyName' => 'Haddad',
            'linkedinUrl' => 'https://www.linkedin.com/in/sami-haddad',
        ]);
        self::assertResponseStatusCodeSame(201);

        $token = $this->loginToken('sami.candidate@example.com', 'password123');
        $this->request('GET', '/v1/me', $token);

        self::assertResponseIsSuccessful();
        $me = $this->jsonResponse();
        self::assertTrue($me['candidate']);
        self::assertSame('Sami', $me['givenName']);
        self::assertNotNull($me['candidateId']);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $this->request('POST', self::ENDPOINT, null, [
            'email' => 'owner@demo.showcase-recruiter-webapp-saas.example', // déjà inscrit (fixtures)
            'password' => 'password123',
            'givenName' => 'Doublon',
            'familyName' => 'Test',
            'linkedinUrl' => 'https://www.linkedin.com/in/doublon-test',
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testValidationRejectsIncompletePayload(): void
    {
        $this->request('POST', self::ENDPOINT, null, [
            'email' => 'not-an-email',
            'password' => 'short',
            'givenName' => '',
            'familyName' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testValidationRequiresLinkedin(): void
    {
        // LinkedIn obligatoire (pivot d'indexation Jarvi) : payload sinon valide mais sans LinkedIn → 422.
        $this->request('POST', self::ENDPOINT, null, [
            'email' => 'no.linkedin@example.com',
            'password' => 'password123',
            'givenName' => 'Sans',
            'familyName' => 'Linkedin',
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
