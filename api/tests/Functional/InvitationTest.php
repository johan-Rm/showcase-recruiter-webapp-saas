<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Recruiter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Invitations & multi-comptes entreprise (tâche 009) : l'admin entreprise invite/retire,
 * le collègue accepte par token (parcours public) et obtient un compte recruteur connectable.
 */
final class InvitationTest extends ApiTestCase
{
    private const string ADMIN_EMAIL = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string RECRUITER_EMAIL = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';
    private const string SEEDED_TOKEN = 'demo-invitation-atlas-member-token';

    public function testCompanyAdminInvitesColleague(): void
    {
        $token = $this->loginToken(self::ADMIN_EMAIL);
        $organizationId = $this->organizationId();

        $this->request('POST', "/v1/organizations/{$organizationId}/invitations", $token, [
            'email' => 'colleague@example.com',
        ]);

        self::assertResponseStatusCodeSame(201);
        $payload = $this->jsonResponse();
        self::assertSame('colleague@example.com', $payload['email']);
        self::assertSame('ROLE_COMPANY_MEMBER', $payload['role']);
        self::assertSame('PENDING', $payload['status']);
        self::assertNotEmpty($payload['token']);
    }

    public function testCompanyRecruiterCannotInvite(): void
    {
        $token = $this->loginToken(self::RECRUITER_EMAIL);
        $organizationId = $this->organizationId();

        $this->request('POST', "/v1/organizations/{$organizationId}/invitations", $token, [
            'email' => 'colleague@example.com',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testNonMemberCannotInvite(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $organizationId = $this->organizationId();

        $this->request('POST', "/v1/organizations/{$organizationId}/invitations", $token, [
            'email' => 'colleague@example.com',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testDuplicatePendingInvitationIsRejected(): void
    {
        $token = $this->loginToken(self::ADMIN_EMAIL);
        $organizationId = $this->organizationId();

        $this->request('POST', "/v1/organizations/{$organizationId}/invitations", $token, ['email' => 'twice@example.com']);
        self::assertResponseStatusCodeSame(201);

        $this->request('POST', "/v1/organizations/{$organizationId}/invitations", $token, ['email' => 'twice@example.com']);
        self::assertResponseStatusCodeSame(409);
    }

    public function testListInvitationsIsCompanyAdminOnly(): void
    {
        $organizationId = $this->organizationId();

        $adminToken = $this->loginToken(self::ADMIN_EMAIL);
        $this->request('GET', "/v1/organizations/{$organizationId}/invitations", $adminToken);
        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $this->jsonResponse()['total']);

        $recruiterToken = $this->loginToken(self::RECRUITER_EMAIL);
        $this->request('GET', "/v1/organizations/{$organizationId}/invitations", $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testInvitationAcceptanceContextIsPublic(): void
    {
        $this->request('GET', '/v1/invitations/'.self::SEEDED_TOKEN);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame('future.recruiter@atlas-talent.example', $payload['email']);
        self::assertSame('Atlas Talent Conseil', $payload['organizationName']);
        self::assertSame('ROLE_COMPANY_MEMBER', $payload['role']);
        self::assertNotEmpty($payload['expiresAt']);
    }

    public function testAcceptValidTokenCreatesConnectableRecruiter(): void
    {
        $this->request('POST', '/v1/invitations/'.self::SEEDED_TOKEN.'/accept', null, [
            'password' => 's3cret-password',
            'givenName' => 'Nadia',
            'familyName' => 'Bennani',
        ]);

        self::assertResponseStatusCodeSame(201);
        $payload = $this->jsonResponse();
        self::assertSame('Nadia', $payload['givenName']);
        self::assertSame('ACTIVE', $payload['status']);

        // Le nouveau membre peut se connecter.
        $token = $this->loginToken('future.recruiter@atlas-talent.example', 's3cret-password');
        self::assertNotEmpty($token);
    }

    public function testAcceptUnknownTokenReturns404(): void
    {
        $this->request('POST', '/v1/invitations/unknown-token/accept', null, [
            'password' => 's3cret-password',
            'givenName' => 'Nadia',
            'familyName' => 'Bennani',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testAcceptAlreadyAcceptedTokenReturns410(): void
    {
        $payload = [
            'password' => 's3cret-password',
            'givenName' => 'Nadia',
            'familyName' => 'Bennani',
        ];

        $this->request('POST', '/v1/invitations/'.self::SEEDED_TOKEN.'/accept', null, $payload);
        self::assertResponseStatusCodeSame(201);

        $this->request('POST', '/v1/invitations/'.self::SEEDED_TOKEN.'/accept', null, $payload);
        self::assertResponseStatusCodeSame(410);
    }

    public function testCompanyAdminRemovesRecruiter(): void
    {
        $token = $this->loginToken(self::ADMIN_EMAIL);
        $organizationId = $this->organizationId();
        $memberRecruiterId = $this->recruiterId(self::RECRUITER_EMAIL);

        $this->request('DELETE', "/v1/organizations/{$organizationId}/members/{$memberRecruiterId}", $token);
        self::assertResponseStatusCodeSame(204);
    }

    public function testRemovedRecruiterCannotAccessOrganizationWithExistingToken(): void
    {
        $adminToken = $this->loginToken(self::ADMIN_EMAIL);
        $memberToken = $this->loginToken(self::RECRUITER_EMAIL);
        $organizationId = $this->organizationId();
        $memberRecruiterId = $this->recruiterId(self::RECRUITER_EMAIL);

        $this->request('DELETE', "/v1/organizations/{$organizationId}/members/{$memberRecruiterId}", $adminToken);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', "/v1/organizations/{$organizationId}", $memberToken);
        self::assertResponseStatusCodeSame(403);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $memberToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testCompanyRecruiterCannotRemove(): void
    {
        $token = $this->loginToken(self::RECRUITER_EMAIL);
        $organizationId = $this->organizationId();
        $ownerRecruiterId = $this->recruiterId(self::ADMIN_EMAIL);

        $this->request('DELETE', "/v1/organizations/{$organizationId}/members/{$ownerRecruiterId}", $token);
        self::assertResponseStatusCodeSame(403);
    }

    public function testCannotRemoveLastCompanyAdmin(): void
    {
        $token = $this->loginToken(self::ADMIN_EMAIL);
        $organizationId = $this->organizationId();
        $ownerRecruiterId = $this->recruiterId(self::ADMIN_EMAIL);

        $this->request('DELETE', "/v1/organizations/{$organizationId}/members/{$ownerRecruiterId}", $token);
        self::assertResponseStatusCodeSame(409);
    }

    private function organizationId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'Atlas Talent Conseil']);
        self::assertNotNull($organization);

        return $organization->getUuid()->toString();
    }

    private function recruiterId(string $personEmail): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['contactEmail' => $personEmail]);
        self::assertNotNull($person);
        $recruiter = $em->getRepository(Recruiter::class)->findOneBy(['person' => $person]);
        self::assertNotNull($recruiter);

        return $recruiter->getUuid()->toString();
    }
}
