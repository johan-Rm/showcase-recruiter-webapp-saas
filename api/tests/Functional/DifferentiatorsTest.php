<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Édition des différenciants candidat (tâche 016) : maîtres côté showcase-recruiter-webapp-saas, self-service, self-only.
 * Écriture locale uniquement : aucun push Jarvi.
 */
final class DifferentiatorsTest extends ApiTestCase
{
    public function testCandidateEditsOwnDifferentiators(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('diff.self@example.com');

        $this->request('PUT', "/v1/candidates/{$candidateId}/differentiators", $token, [
            'availability' => 'NOTICE_1M',
            'returnHorizon' => 'M6',
            'geoMobility' => true,
            'salaryExpected' => ['value' => 4500000, 'currency' => 'MAD'],
            'salaryMin' => ['value' => 3500000, 'currency' => 'MAD'],
        ]);
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        self::assertSame('NOTICE_1M', $body['availability']);
        self::assertSame('M6', $body['returnHorizon']);
        self::assertTrue($body['geoMobility']);
        self::assertSame(['value' => 4500000, 'currency' => 'MAD'], $body['salaryExpected']);
        self::assertSame(['value' => 3500000, 'currency' => 'MAD'], $body['salaryMin']);
        self::assertArrayNotHasKey('syncStatus', $body);

        // Persistance : relecture cohérente.
        $this->request('GET', "/v1/candidates/{$candidateId}/differentiators", $token);
        self::assertResponseIsSuccessful();
        self::assertSame('NOTICE_1M', $this->jsonResponse()['availability']);
    }

    public function testCannotEditAnotherCandidatesDifferentiators(): void
    {
        [, $candidateA] = $this->onboardAndLogin('diff.a@example.com');
        [$tokenB] = $this->onboardAndLogin('diff.b@example.com');

        $this->request('PUT', "/v1/candidates/{$candidateA}/differentiators", $tokenB, ['geoMobility' => true]);
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
