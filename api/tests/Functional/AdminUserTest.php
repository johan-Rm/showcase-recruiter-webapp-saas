<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Comptes & droits admin (tâche 100) : GET /v1/admin/users — liste cross-tenant des comptes,
 * gardée ROLE_ADMIN. Lecture seule. La dernière connexion (lastLoginAt) est vérifiée en dev.
 */
final class AdminUserTest extends ApiTestCase
{
    public function testAdminListsUsers(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/users', $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertIsArray($payload['items']);
        self::assertGreaterThan(0, $payload['total']);
    }

    public function testRequiresAdmin(): void
    {
        $recruiterToken = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/users', $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testRequiresAuthentication(): void
    {
        $this->request('GET', '/v1/admin/users', null);
        self::assertResponseStatusCodeSame(401);
    }
}
