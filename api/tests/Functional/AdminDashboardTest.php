<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Centre de contrôle admin (tâche 102) : GET /v1/admin/dashboard — compteurs temps réel,
 * gardé ROLE_ADMIN. Lecture seule.
 */
final class AdminDashboardTest extends ApiTestCase
{
    public function testAdminSeesCounters(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/dashboard', $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertIsInt($payload['organizationsInReview']);
        self::assertIsInt($payload['loginsLast7Days']);
        self::assertGreaterThanOrEqual($payload['candidatesActive'], $payload['candidatesTotal']);
    }

    public function testRequiresAdmin(): void
    {
        $recruiterToken = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/dashboard', $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testRequiresAuthentication(): void
    {
        $this->request('GET', '/v1/admin/dashboard', null);
        self::assertResponseStatusCodeSame(401);
    }
}
