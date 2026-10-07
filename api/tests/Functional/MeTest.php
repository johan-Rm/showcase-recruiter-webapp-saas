<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * /v1/me : utilisateur courant, protégé par JWT (tâche 003).
 */
final class MeTest extends ApiTestCase
{
    public function testMeRequiresAuthentication(): void
    {
        $this->client->request('GET', '/v1/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsCurrentUserContext(): void
    {
        $token = $this->loginToken();

        $this->client->request('GET', '/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame('owner@demo.showcase-recruiter-webapp-saas.example', $payload['email']);
        self::assertSame('Sara', $payload['givenName']);
        self::assertTrue($payload['recruiter']);
        self::assertFalse($payload['candidate']);
        self::assertContains('ROLE_COMPANY_OWNER', $payload['roles']);
        // Sans elle, le client ne peut construire aucune URL de crédit ni de dévoilement.
        self::assertNotNull($payload['organizationId'], 'un recruteur actif annonce son organisation');
    }

    /** Qui n'est pas recruteur n'a pas d'organisation : le champ existe et vaut `null`. */
    public function testANonRecruiterAnnouncesNoOrganization(): void
    {
        $token = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');

        $this->client->request('GET', '/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertFalse($payload['recruiter']);
        self::assertNull($payload['organizationId']);
    }
}
