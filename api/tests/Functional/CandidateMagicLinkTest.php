<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Entity\User;
use App\Repository\CandidateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Magic Link candidat (tâche 033) : rattachement d'un compte ROLE_CANDIDATE à une projection déjà
 * importée (par email de contact) sans doublon, idempotence, et consentement explicite horodaté
 * self-service.
 */
final class CandidateMagicLinkTest extends ApiTestCase
{
    public function testMagicLinkAttachesCandidateAccountToImportedProjection(): void
    {
        $candidates = self::getContainer()->get(CandidateRepository::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $before = $candidates->count([]);

        // yassine@example.com = projection de fixture SANS compte (Person.contactEmail renseigné).
        $this->request('POST', '/v1/auth/magic-link/request', null, ['email' => 'yassine@example.com']);
        self::assertResponseIsSuccessful();

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'yassine@example.com']);
        self::assertInstanceOf(User::class, $user);
        self::assertContains('ROLE_CANDIDATE', $user->getRoles());

        // Rattaché au Person EXISTANT, aucune nouvelle projection créée.
        self::assertSame($before, $candidates->count([]));
        $candidate = $candidates->findOneByContactEmail('yassine@example.com');
        self::assertInstanceOf(Candidate::class, $candidate);
        self::assertSame($candidate->getPerson()->getId(), $user->getPerson()->getId());
    }

    public function testMagicLinkRequestIsIdempotent(): void
    {
        $candidates = self::getContainer()->get(CandidateRepository::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $before = $candidates->count([]);

        $this->request('POST', '/v1/auth/magic-link/request', null, ['email' => 'imane@example.com']);
        self::assertResponseIsSuccessful();
        $this->request('POST', '/v1/auth/magic-link/request', null, ['email' => 'imane@example.com']);
        self::assertResponseIsSuccessful();

        $users = $em->getRepository(User::class)->findBy(['email' => 'imane@example.com']);
        self::assertCount(1, $users);
        self::assertSame($before, $candidates->count([]));
    }

    public function testFindOneByContactEmailMatchesJarviReplicaContacts(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidates = self::getContainer()->get(CandidateRepository::class);

        // Profil importé « minimal » : Person sans contactEmail, email seulement dans la réplica Jarvi.
        $person = new Person('Jarvi', 'Only', country: 'FR', city: 'Lille');
        $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
        $jarvi = new CandidateJarvi($candidate, contacts: [
            ['type' => 'email', 'value' => 'Jarvi.Only@example.com', 'source' => 'LINKEDIN'],
        ]);
        $candidate->setJarvi($jarvi);
        $em->persist($person);
        $em->persist($candidate);
        $em->persist($jarvi);
        $em->flush();

        $found = $candidates->findOneByContactEmail('jarvi.only@example.com');
        self::assertInstanceOf(Candidate::class, $found);
        self::assertSame($candidate->getId(), $found->getId());
    }

    public function testConsentIsExplicitTimestampedAndSelfOnly(): void
    {
        [$token, $candidateId] = $this->onboardCandidate('consent.self@example.com');

        // Défaut : pas de consentement.
        $this->request('GET', "/v1/candidates/{$candidateId}/consent", $token);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->jsonResponse()['consent']);
        self::assertNull($this->jsonResponse()['consentGivenAt']);

        // Consentement explicite → horodaté + source.
        $this->request('PUT', "/v1/candidates/{$candidateId}/consent", $token, ['consent' => true]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['consent']);
        self::assertNotNull($this->jsonResponse()['consentGivenAt']);
        self::assertSame('SELF_SERVICE', $this->jsonResponse()['consentSource']);

        // Retrait du consentement.
        $this->request('PUT', "/v1/candidates/{$candidateId}/consent", $token, ['consent' => false]);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->jsonResponse()['consent']);
        self::assertNull($this->jsonResponse()['consentGivenAt']);

        // Self-only : un autre candidat ne peut pas modifier ce consentement.
        [$otherToken] = $this->onboardCandidate('consent.other@example.com');
        $this->request('PUT', "/v1/candidates/{$candidateId}/consent", $otherToken, ['consent' => true]);
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return array{0: string, 1: string} [token, candidateId]
     */
    private function onboardCandidate(string $email): array
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
