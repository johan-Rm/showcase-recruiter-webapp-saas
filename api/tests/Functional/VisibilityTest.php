<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Visibilité candidat (tâche 018) : choix self-service + trace de consentement horodatée, self-only.
 */
final class VisibilityTest extends ApiTestCase
{
    public function testCandidateSetsOwnVisibility(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('vis.self@example.com');

        // Défaut = VISIBLE, pas encore de consentement horodaté.
        $this->request('GET', "/v1/candidates/{$candidateId}/visibility", $token);
        self::assertResponseIsSuccessful();
        self::assertSame('VISIBLE', $this->jsonResponse()['visibility']);
        self::assertNull($this->jsonResponse()['visibilityUpdatedAt']);

        // Passage en HIDDEN → horodaté.
        $this->request('PUT', "/v1/candidates/{$candidateId}/visibility", $token, ['visibility' => 'HIDDEN']);
        self::assertResponseIsSuccessful();
        self::assertSame('HIDDEN', $this->jsonResponse()['visibility']);
        self::assertNotNull($this->jsonResponse()['visibilityUpdatedAt']);

        $this->request('GET', "/v1/candidates/{$candidateId}/visibility", $token);
        self::assertSame('HIDDEN', $this->jsonResponse()['visibility']);
    }

    public function testCannotChangeAnotherCandidatesVisibility(): void
    {
        [, $candidateA] = $this->onboardAndLogin('vis.a@example.com');
        [$tokenB] = $this->onboardAndLogin('vis.b@example.com');

        $this->request('PUT', "/v1/candidates/{$candidateA}/visibility", $tokenB, ['visibility' => 'HIDDEN']);
        self::assertResponseStatusCodeSame(403);
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
