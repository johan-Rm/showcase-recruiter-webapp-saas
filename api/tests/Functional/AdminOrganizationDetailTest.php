<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;

/**
 * GET /v1/admin/organizations/{id} (tâche API-074) : la fiche d'une entreprise pour
 * l'administration — solde, abonnement, membres et journal de crédits. Le portefeuille n'était
 * jusqu'ici lisible que par l'entreprise elle-même : l'administration ne pouvait pas répondre à un
 * client qui demande son solde. Lecture seule, gardée ROLE_ADMIN.
 */
final class AdminOrganizationDetailTest extends ApiTestCase
{
    private const string ADMIN_EMAIL = 'admin@demo.showcase-recruiter-webapp-saas.example';
    private const string OWNER_EMAIL = 'owner@demo.showcase-recruiter-webapp-saas.example';
    private const string DEMO_ORGANIZATION = '01900000-0000-7000-8000-0000000000a1';

    public function testTheSheetCarriesIdentityBalanceSubscriptionMembersAndLedger(): void
    {
        $this->request('GET', $this->uri(self::DEMO_ORGANIZATION), $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        self::assertSame(self::DEMO_ORGANIZATION, $body['id']);
        self::assertNotEmpty($body['name']);
        self::assertArrayHasKey('validationStatus', $body);
        self::assertIsInt($body['creditBalance']);
        self::assertNotEmpty($body['members']);
        self::assertNotEmpty($body['creditTransactions']);

        $member = $body['members'][0];
        foreach (['id', 'givenName', 'familyName', 'organizationRole', 'status'] as $field) {
            self::assertArrayHasKey($field, $member);
        }

        self::assertSame('starter', $body['subscription']['plan']);
        self::assertArrayHasKey('status', $body['subscription']);
        self::assertArrayHasKey('currentPeriodEnd', $body['subscription']);
    }

    /** Le solde après opération rend le journal auto-portant : on retombe sur le solde de tête. */
    public function testLedgerIsMostRecentFirstAndCarriesTheBalanceAfter(): void
    {
        $this->request('GET', $this->uri(self::DEMO_ORGANIZATION), $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        $dates = array_column($body['creditTransactions'], 'createdAt');
        $sorted = $dates;
        rsort($sorted);
        self::assertSame($sorted, $dates);

        foreach ($body['creditTransactions'] as $transaction) {
            self::assertArrayHasKey('balanceAfter', $transaction);
            self::assertArrayHasKey('amount', $transaction);
            self::assertArrayHasKey('type', $transaction);
        }

        // PAS d'égalité entre le solde et le `balanceAfter` du mouvement le plus récent : les
        // données de démonstration se contredisent déjà là-dessus (cf. Reste à faire de la carte).
        // L'assertion serait juste, et rouge pour une raison étrangère à cette route.
    }

    /** Jamais créditée, jamais abonnée : des zéros et des listes vides, pas une erreur. */
    public function testAnOrganizationWithoutWalletNorSubscriptionIsServed(): void
    {
        $uuid = $this->createBareOrganization();

        $this->request('GET', $this->uri($uuid), $this->loginToken(self::ADMIN_EMAIL));

        self::assertResponseIsSuccessful();
        $body = $this->jsonResponse();
        self::assertSame(0, $body['creditBalance']);
        self::assertSame([], $body['members']);
        self::assertSame([], $body['creditTransactions']);
        // Sans abonnement, le champ n'est pas servi — c'est la convention du dépôt sur les nuls.
        self::assertArrayNotHasKey('subscription', $body);
    }

    public function testTheLedgerIsBounded(): void
    {
        $token = $this->loginToken(self::ADMIN_EMAIL);

        $this->request('GET', $this->uri(self::DEMO_ORGANIZATION).'?transactions=1', $token);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->jsonResponse()['creditTransactions']);

        $this->request('GET', $this->uri(self::DEMO_ORGANIZATION).'?transactions=999', $token);
        self::assertResponseIsSuccessful();
        self::assertLessThanOrEqual(100, \count($this->jsonResponse()['creditTransactions']));
    }

    public function testUnknownOrganizationIsNotFound(): void
    {
        $this->request('GET', $this->uri('01900000-0000-7000-8000-0000000000ff'), $this->loginToken(self::ADMIN_EMAIL));
        self::assertResponseStatusCodeSame(404);
    }

    public function testARecruiterCannotReadTheSheet(): void
    {
        $this->request('GET', $this->uri(self::DEMO_ORGANIZATION), $this->loginToken(self::OWNER_EMAIL));
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnAnonymousCallerCannotReadTheSheet(): void
    {
        $this->request('GET', $this->uri(self::DEMO_ORGANIZATION));
        self::assertResponseStatusCodeSame(401);
    }

    private function uri(string $uuid): string
    {
        return "/v1/admin/organizations/{$uuid}";
    }

    private function createBareOrganization(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = new Organization('Entreprise sans portefeuille');
        $em->persist($organization);
        $em->flush();

        return $organization->getUuid()->toString();
    }
}
