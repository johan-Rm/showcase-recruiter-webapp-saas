<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Security\SuspendedAccountChecker;

/**
 * Onboarding entreprise (tâche 004) : création + admin entreprise, isolation multi-tenant,
 * admin-only. Fixtures : owner@demo (déjà admin d'une entreprise), newcomer@demo (sans entreprise).
 */
final class OrganizationTest extends ApiTestCase
{
    public function testDeclarantCreatesOrganizationAndBecomesCompanyAdmin(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');

        // Identité distincte du fixture (Nadia Cherkaoui) pour prouver la mise à jour du déclarant.
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Sophie', 'familyName' => 'Durand', 'country' => 'MA', 'city' => 'Rabat', 'acquisitionChannel' => 'LINKEDIN']);
        self::assertResponseStatusCodeSame(201);

        $organization = $this->jsonResponse();
        self::assertSame('Atlas Recrut', $organization['name']);
        self::assertSame('EN_REVUE', $organization['validationStatus']);
        self::assertSame('PENDING', $organization['jarviCompanySyncStatus']);
        self::assertNull($organization['jarviCompanyId']);
        $id = $organization['id'];

        // Le créateur est admin de la nouvelle entreprise, avec l'identité saisie à l'inscription.
        $this->request('GET', "/v1/organizations/{$id}/members", $token);
        self::assertResponseIsSuccessful();
        $members = $this->jsonResponse();
        self::assertSame(1, $members['total']);
        self::assertSame('ROLE_COMPANY_OWNER', $members['items'][0]['organizationRole']);
        self::assertSame('nadia.cherkaoui@entreprise-abc.test', $members['items'][0]['contactEmail']);
        self::assertSame('Sophie', $members['items'][0]['givenName']);
        self::assertSame('Durand', $members['items'][0]['familyName']);
    }

    public function testCreatingOrganizationRequiresDeclarantIdentity(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut']);
        self::assertResponseStatusCodeSame(422);
    }

    /**
     * La revue ne garde plus la porte : l'accès s'ouvre à l'inscription, avant toute décision
     * humaine. Ce test remplace celui qui vérifiait l'inverse.
     */
    public function testOrganizationUnderReviewAlreadyHasAccess(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];
        self::assertSame('EN_REVUE', $this->jsonResponse()['validationStatus']);

        // Le jeton d'inscription ne porte pas encore le rôle : c'est la connexion suivante qui l'a.
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/v1/searches', $token);
        self::assertResponseIsSuccessful();
    }

    /**
     * Le refus coupe l'accès sur deux fronts : la session déjà ouverte, qu'aucune révocation de
     * jeton ne pourrait atteindre, et la connexion suivante — avec un motif que l'interface doit
     * pouvoir distinguer d'un mot de passe faux.
     */
    public function testRejectionSuspendsOpenSessionAndSaysWhyAtLogin(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];

        $openSession = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('POST', "/v1/organizations/{$organizationId}/reject", $adminToken);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/v1/searches', $openSession);
        self::assertResponseStatusCodeSame(401);

        $this->request('POST', '/v1/auth/login', null, ['email' => 'nadia.cherkaoui@entreprise-abc.test', 'password' => 'password']);
        self::assertResponseStatusCodeSame(401);
        self::assertSame(SuspendedAccountChecker::SUSPENDED_MESSAGE, $this->jsonResponse()['message']);
    }

    /** Un refus se répare : rien n'ayant été recopié sur le compte, il n'y a rien à défaire. */
    public function testRevalidationRestoresAccess(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('POST', "/v1/organizations/{$organizationId}/reject", $adminToken);
        $this->request('POST', "/v1/organizations/{$organizationId}/validate", $adminToken);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/v1/searches', $this->loginToken('nadia.cherkaoui@entreprise-abc.test'));
        self::assertResponseIsSuccessful();
    }

    /** La suspension porte sur l'entreprise : un membre invité tombe avec elle, pas seulement son propriétaire. */
    public function testRejectionAlsoSuspendsInvitedMember(): void
    {
        $memberToken = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/searches', $memberToken);
        self::assertResponseIsSuccessful();

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/organizations?status=VALIDEE', $adminToken);
        $organizationId = null;
        foreach ($this->jsonResponse()['items'] as $item) {
            if ('Atlas Talent Conseil' === $item['name']) {
                $organizationId = $item['id'];
            }
        }
        self::assertNotNull($organizationId);

        $this->request('POST', "/v1/organizations/{$organizationId}/reject", $adminToken);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/v1/searches', $memberToken);
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminValidatesOrganizationWithoutTriggeringJarviWhenDisabled(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('POST', "/v1/organizations/{$organizationId}/validate", $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame('VALIDEE', $payload['validationStatus']);
        self::assertSame('PENDING', $payload['jarviCompanySyncStatus']);
        self::assertNull($payload['jarviCompanyId']);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertResponseIsSuccessful();
    }

    public function testNonAdminCannotValidateOrganization(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];

        $this->request('POST', "/v1/organizations/{$organizationId}/validate", $token);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminRejectsOrganization(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('POST', "/v1/organizations/{$organizationId}/reject", $adminToken);
        self::assertResponseIsSuccessful();

        self::assertSame('REFUSE', $this->jsonResponse()['validationStatus']);
    }

    public function testAcquisitionChannelIsRequiredAndFrozenAtRegistration(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $identity = ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui'];

        $this->request('POST', '/v1/organizations', $token, $identity);
        self::assertResponseStatusCodeSame(422);

        // « Autre » sans précision : la valeur seule n'apprend rien, la précision est exigée.
        $this->request('POST', '/v1/organizations', $token, [...$identity, 'acquisitionChannel' => 'AUTRE']);
        self::assertResponseStatusCodeSame(422);

        $this->request('POST', '/v1/organizations', $token, [
            ...$identity,
            'acquisitionChannel' => 'AUTRE',
            'acquisitionChannelDetail' => 'Podcast recrutement',
            'jobTitle' => 'Talent Manager',
        ]);
        self::assertResponseStatusCodeSame(201);

        $organization = $this->jsonResponse();
        self::assertSame('AUTRE', $organization['acquisitionChannel']);
        self::assertSame('Podcast recrutement', $organization['acquisitionChannelDetail']);

        $this->request('GET', "/v1/organizations/{$organization['id']}/members", $token);
        self::assertSame('Talent Manager', $this->jsonResponse()['items'][0]['jobTitle']);

        // Donnée d'acquisition, pas donnée de profil : le PUT ne la réécrit pas.
        $this->request('PUT', "/v1/organizations/{$organization['id']}", $token, [
            ...$identity,
            'acquisitionChannel' => 'LINKEDIN',
            'description' => 'Cabinet de recrutement tech.',
        ]);
        self::assertResponseIsSuccessful();

        $updated = $this->jsonResponse();
        self::assertSame('AUTRE', $updated['acquisitionChannel']);
        self::assertSame('Cabinet de recrutement tech.', $updated['description']);
    }

    /**
     * Le secteur est une LISTE FERMÉE (config/referentiels/secteurs.yaml) : le front en fait un
     * dropdown, `Assert\Choice` referme la porte côté serveur. Un libellé hors référentiel est rejeté,
     * un libellé du référentiel est accepté et stocké tel quel.
     */
    public function testSectorMustBelongToReferential(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $identity = ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN'];

        $this->request('POST', '/v1/organizations', $token, [...$identity, 'sector' => 'Cabinet de conseil RH']);
        self::assertResponseStatusCodeSame(422);

        $this->request('POST', '/v1/organizations', $token, [...$identity, 'sector' => 'Conseil et audit']);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('Conseil et audit', $this->jsonResponse()['sector']);
    }

    public function testAdminListsOrganizationsAwaitingReview(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $organizationId = $this->jsonResponse()['id'];

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/organizations?status=EN_REVUE', $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        $ids = array_column($payload['items'], 'id');
        self::assertContains($organizationId, $ids);
        self::assertSame(['EN_REVUE'], array_values(array_unique(array_column($payload['items'], 'validationStatus'))));

        // Une fois validée, l'entreprise sort de la file.
        $this->request('POST', "/v1/organizations/{$organizationId}/validate", $adminToken);
        $this->request('GET', '/v1/admin/organizations?status=EN_REVUE', $adminToken);
        self::assertNotContains($organizationId, array_column($this->jsonResponse()['items'], 'id'));
    }

    public function testReviewQueueRejectsUnknownStatusAndNonAdmin(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/organizations?status=INCONNU', $adminToken);
        self::assertResponseStatusCodeSame(400);

        $recruiterToken = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/organizations', $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testSecondOrganizationIsRejected(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');

        $this->request('POST', '/v1/organizations', $token, ['name' => 'Première', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        self::assertResponseStatusCodeSame(201);

        $this->request('POST', '/v1/organizations', $token, ['name' => 'Seconde', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        self::assertResponseStatusCodeSame(409);
    }

    public function testNonMemberCannotViewOrganization(): void
    {
        $declarant = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $declarant, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $id = $this->jsonResponse()['id'];

        // owner@demo appartient à une AUTRE entreprise → isolation.
        $other = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', "/v1/organizations/{$id}", $other);
        self::assertResponseStatusCodeSame(403);
    }

    public function testCompanyAdminCanUpdateButNonMemberCannot(): void
    {
        $declarant = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $declarant, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        $id = $this->jsonResponse()['id'];

        $this->request('PUT', "/v1/organizations/{$id}", $declarant, ['name' => 'Atlas Recrutement', 'city' => 'Casablanca']);
        self::assertResponseIsSuccessful();
        self::assertSame('Atlas Recrutement', $this->jsonResponse()['name']);

        $other = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('PUT', "/v1/organizations/{$id}", $other, ['name' => 'Pirate']);
        self::assertResponseStatusCodeSame(403);
    }

    public function testValidatedOrganizationCanChangeIdentityWithoutRevalidation(): void
    {
        $declarant = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $declarant, [
            'name' => 'Atlas Recrut',
            'givenName' => 'Nadia',
            'familyName' => 'Cherkaoui',
            'legalName' => 'Atlas Recrut SARL',
            'acquisitionChannel' => 'LINKEDIN',
        ]);
        $id = $this->jsonResponse()['id'];

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('POST', "/v1/organizations/{$id}/validate", $adminToken);
        self::assertResponseIsSuccessful();

        // Push Jarvi unique à la validation, aucune synchro ensuite : l'identité d'une entreprise
        // validée est modifiable librement, sans re-validation ni re-push.
        $this->request('PUT', "/v1/organizations/{$id}", $declarant, [
            'name' => 'Atlas Recrutement',
            'legalName' => 'Atlas Recrutement SARL',
            'city' => 'Casablanca',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('Atlas Recrutement', $this->jsonResponse()['name']);
        self::assertSame('Casablanca', $this->jsonResponse()['city']);
    }

    public function testCreatingOrganizationRequiresAuthentication(): void
    {
        $this->request('POST', '/v1/organizations', null, ['name' => 'Anon']);
        self::assertResponseStatusCodeSame(401);
    }

    public function testBlankNameIsRejected(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, ['name' => '']);
        self::assertResponseStatusCodeSame(422);
    }
}
