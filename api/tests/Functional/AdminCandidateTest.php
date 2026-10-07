<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Vivier admin (tâche 099) : GET /v1/admin/candidates — liste cross-tenant NON masquée, gardée
 * ROLE_ADMIN, paginée. On garde ici l'enveloppe, la garde de rôle et le plafond de pagination ;
 * le non-masquage (nom complet, coordonnées, employeur en clair) est prouvé de bout en bout en dev.
 */
final class AdminCandidateTest extends ApiTestCase
{
    public function testAdminListsCandidates(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates', $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertIsArray($payload['items']);
        self::assertIsInt($payload['total']);
        self::assertSame(1, $payload['page']);
    }

    public function testPerPageIsClampedToMax(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates?perPage=999', $adminToken);
        self::assertResponseIsSuccessful();
        self::assertLessThanOrEqual(100, $this->jsonResponse()['perPage']);
    }

    public function testListingRequiresAdmin(): void
    {
        $recruiterToken = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates', $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testListingRequiresAuthentication(): void
    {
        $this->request('GET', '/v1/admin/candidates', null);
        self::assertResponseStatusCodeSame(401);
    }

    public function testDetailRequiresAdmin(): void
    {
        $recruiterToken = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates/00000000-0000-0000-0000-000000000000', $recruiterToken);
        self::assertResponseStatusCodeSame(403);
    }

    public function testDetailRequiresAuthentication(): void
    {
        $this->request('GET', '/v1/admin/candidates/00000000-0000-0000-0000-000000000000', null);
        self::assertResponseStatusCodeSame(401);
    }

    public function testUnknownCandidateReturns404(): void
    {
        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates/00000000-0000-0000-0000-000000000000', $adminToken);
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Cas MAJORITAIRE du vivier (1698 profils sourcés) : `Person` est vide, tout vit dans la
     * réplica Jarvi. Sans repli sur la réplica, la fiche affichait « aucune coordonnée » alors
     * qu'on détenait e-mail, téléphone et LinkedIn.
     */
    public function testSourcedProfileExposesCoordinatesHeldInTheJarviReplica(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $person = new Person('Sourcé', 'Jarvi', country: 'FR', city: 'Lille');
        $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
        $jarvi = new CandidateJarvi(
            $candidate,
            contacts: [
                ['type' => 'email', 'value' => 'cv@example.com', 'source' => 'RESUME'],
                ['type' => 'email', 'value' => 'saisi@example.com', 'source' => 'MANUAL'],
                ['type' => 'phone', 'value' => '00212 6 19 81 39 59', 'source' => 'RESUME', 'whatsappIdentifier' => '212619813959@s.whatsapp.net'],
            ],
            rawPayload: ['profileUrl' => 'https://www.linkedin.com/in/source-jarvi'],
        );
        $candidate->setJarvi($jarvi);
        $em->persist($person);
        $em->persist($candidate);
        $em->persist($jarvi);
        $em->flush();

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates/'.$candidate->getUuid(), $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame('saisi@example.com', $payload['contactEmail']); // MANUAL prime sur RESUME
        self::assertSame('+212619813959', $payload['phone']);            // normalisé en E.164
        self::assertSame('+212619813959', $payload['whatsappNumber']);   // même numéro, cohérent
        self::assertSame('https://www.linkedin.com/in/source-jarvi', $payload['linkedinUrl']);
    }

    /** Le candidat inscrit reste maître de ses coordonnées : `Person` prime sur la réplica. */
    public function testDeclaredCoordinatesWinOverTheReplica(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $person = new Person('Inscrit', 'Jarvi', contactEmail: 'declare@example.com', phone: '+33600000001', country: 'FR', city: 'Lille');
        $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
        $jarvi = new CandidateJarvi($candidate, contacts: [
            ['type' => 'email', 'value' => 'ancien@example.com', 'source' => 'MANUAL'],
            ['type' => 'phone', 'value' => '+212600000000', 'source' => 'MANUAL'],
        ]);
        $candidate->setJarvi($jarvi);
        $em->persist($person);
        $em->persist($candidate);
        $em->persist($jarvi);
        $em->flush();

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/candidates/'.$candidate->getUuid(), $adminToken);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame('declare@example.com', $payload['contactEmail']);
        self::assertSame('+33600000001', $payload['phone']);
    }
}
