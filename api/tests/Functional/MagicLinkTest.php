<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\MagicLinkToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class MagicLinkTest extends ApiTestCase
{
    /**
     * Une adresse inconnue est le cas NOMINAL d'une inscription (entreprise ou candidat) : le lien
     * doit partir. Mais aucun compte n'est créé ici — sinon la route publique permettrait de peupler
     * la table des comptes à volonté. Le compte naît à la consommation du lien (cf. testConsume…).
     */
    public function testRequestForUnknownEmailIssuesLinkWithoutCreatingAccount(): void
    {
        $this->request('POST', '/v1/auth/magic-link/request', null, [
            'email' => 'sara.bennani@entreprise-abc.test',
        ]);

        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['sent']);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertNotNull($em->getRepository(MagicLinkToken::class)->findOneBy([
            'email' => 'sara.bennani@entreprise-abc.test',
        ]));
        self::assertNull($em->getRepository(User::class)->findOneBy([
            'email' => 'sara.bennani@entreprise-abc.test',
        ]));
    }

    public function testRequestForExistingUserCreatesTokenWithoutExternalSmtp(): void
    {
        $this->request('POST', '/v1/auth/magic-link/request', null, [
            'email' => 'nadia.cherkaoui@entreprise-abc.test',
        ]);

        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['sent']);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(MagicLinkToken::class, $em->getRepository(MagicLinkToken::class)->findOneBy([
            'email' => 'nadia.cherkaoui@entreprise-abc.test',
        ]));
    }

    public function testConsumeReturnsJwtAndConsumesToken(): void
    {
        $plainToken = 'valid-magic-token';
        $this->persistMagicToken('nadia.cherkaoui@entreprise-abc.test', $plainToken, new DateTimeImmutable('+30 minutes'));

        $this->request('POST', '/v1/auth/magic-link/consume', null, ['token' => $plainToken]);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame('nadia.cherkaoui@entreprise-abc.test', $payload['email']);
        self::assertNotEmpty($payload['token']);

        $this->request('GET', '/v1/me', (string) $payload['token']);
        self::assertResponseIsSuccessful();
    }

    /**
     * L'inscription entreprise repose là-dessus : le déclarant est inconnu quand il demande son lien,
     * et son compte doit exister quand il atterrit — sans rôle (il l'obtiendra en créant l'entreprise).
     */
    public function testConsumeCreatesTheAccountOfAnUnknownEmail(): void
    {
        $plainToken = 'newcomer-magic-token';
        $this->persistMagicToken('inconnu@nouvelle-boite.test', $plainToken, new DateTimeImmutable('+30 minutes'));

        $this->request('POST', '/v1/auth/magic-link/consume', null, ['token' => $plainToken]);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame('inconnu@nouvelle-boite.test', $payload['email']);

        $user = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findOneBy(['email' => 'inconnu@nouvelle-boite.test']);

        self::assertNotNull($user);
        self::assertSame(['ROLE_USER'], $user->getRoles());

        // La session ouverte par le lien doit suffire à créer l'entreprise (POST /v1/organizations).
        $this->request('GET', '/v1/me', (string) $payload['token']);
        self::assertResponseIsSuccessful();
    }

    public function testConsumedTokenCannotBeReused(): void
    {
        $plainToken = 'single-use-magic-token';
        $this->persistMagicToken('nadia.cherkaoui@entreprise-abc.test', $plainToken, new DateTimeImmutable('+30 minutes'));

        $this->request('POST', '/v1/auth/magic-link/consume', null, ['token' => $plainToken]);
        self::assertResponseIsSuccessful();

        $this->request('POST', '/v1/auth/magic-link/consume', null, ['token' => $plainToken]);
        self::assertResponseStatusCodeSame(410);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $plainToken = 'expired-magic-token';
        $this->persistMagicToken('nadia.cherkaoui@entreprise-abc.test', $plainToken, new DateTimeImmutable('-1 minute'));

        $this->request('POST', '/v1/auth/magic-link/consume', null, ['token' => $plainToken]);
        self::assertResponseStatusCodeSame(410);
    }

    private function persistMagicToken(string $email, string $plainToken, DateTimeImmutable $expiresAt): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new MagicLinkToken($email, hash('sha256', $plainToken), $expiresAt));
        $em->flush();
    }
}
