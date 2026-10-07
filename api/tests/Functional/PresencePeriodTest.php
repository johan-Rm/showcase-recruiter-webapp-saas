<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Présence au Maroc (tâche 017) : CRUD self-service, validation endDate ≥ startDate, self-only.
 */
final class PresencePeriodTest extends ApiTestCase
{
    public function testCandidateManagesOwnPresencePeriods(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('pp.crud@example.com');

        $this->request('POST', "/v1/candidates/{$candidateId}/presence-periods", $token, [
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-15',
            'city' => 'Casablanca',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->jsonResponse();
        self::assertSame('2026-09-01', $created['startDate']);
        self::assertSame('Casablanca', $created['city']);
        $id = $created['id'];

        $this->request('GET', "/v1/candidates/{$candidateId}/presence-periods", $token);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['total']);

        $this->request('PUT', "/v1/candidates/{$candidateId}/presence-periods/{$id}", $token, [
            'startDate' => '2026-10-01',
            'endDate' => '2026-10-10',
            'city' => 'Rabat',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('Rabat', $this->jsonResponse()['city']);

        $this->request('DELETE', "/v1/candidates/{$candidateId}/presence-periods/{$id}", $token);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', "/v1/candidates/{$candidateId}/presence-periods", $token);
        self::assertSame(0, $this->jsonResponse()['total']);
    }

    public function testEndDateBeforeStartDateIsRejected(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('pp.invalid@example.com');

        $this->request('POST', "/v1/candidates/{$candidateId}/presence-periods", $token, [
            'startDate' => '2026-09-15',
            'endDate' => '2026-09-01',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testCannotManageAnotherCandidatesPeriods(): void
    {
        [, $candidateA] = $this->onboardAndLogin('pp.a@example.com');
        [$tokenB] = $this->onboardAndLogin('pp.b@example.com');

        $this->request('POST', "/v1/candidates/{$candidateA}/presence-periods", $tokenB, [
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-15',
        ]);

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
