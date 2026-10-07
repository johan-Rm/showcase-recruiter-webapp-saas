<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Supervision Jarvi (tâche 101) : GET /v1/admin/jarvi — état des flux + agrégats du vivier,
 * gardé ROLE_ADMIN. Lecture seule.
 */
final class AdminJarviTest extends ApiTestCase
{
    public function testAdminSeesSupervisionSnapshot(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/jarvi', $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertIsArray($payload['streams']);
        self::assertIsInt($payload['total']);
        self::assertSame($payload['total'], $payload['active'] + $payload['inactive']);
    }

    public function testRequiresAdmin(): void
    {
        $recruiterToken = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/jarvi', $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testRequiresAuthentication(): void
    {
        $this->request('GET', '/v1/admin/jarvi', null);
        self::assertResponseStatusCodeSame(401);
    }
}
