<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\MagicLinkToken;
use App\Entity\User;
use App\Tests\Support\OidcTestKeys;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Social login (tâche 081) : un id_token OIDC prouvé fait partir un magic link vers l'e-mail vérifié.
 * Le social login n'ouvre PAS de session — il ne fait que déclencher le même lien que la demande par
 * e-mail. Les critères d'acceptation : jeton valide → 202 + lien émis ; jeton non prouvable → 401,
 * aucun lien, aucun compte.
 */
final class SocialMagicLinkTest extends ApiTestCase
{
    private const ISSUER = 'https://accounts.google.com';
    private const AUDIENCE = 'test-client-id';

    /**
     * @param array<string, mixed> $overrides
     */
    private function idToken(array $overrides = []): string
    {
        return OidcTestKeys::sign([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'exp' => time() + 300,
            'iat' => time(),
            'email' => 'newcomer@social-signup.test',
            'email_verified' => true,
            ...$overrides,
        ]);
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testVerifiedTokenIssuesMagicLinkWithoutCreatingAccount(): void
    {
        $this->request('POST', '/v1/auth/social/magic-link', null, [
            'provider' => 'google',
            'idToken' => $this->idToken(),
            'space' => 'entreprise',
        ]);

        self::assertResponseStatusCodeSame(202);
        self::assertTrue($this->jsonResponse()['sent']);

        // Le lien est parti vers l'e-mail PROUVÉ par le jeton — pas vers une adresse annoncée.
        self::assertNotNull($this->em()->getRepository(MagicLinkToken::class)->findOneBy([
            'email' => 'newcomer@social-signup.test',
        ]));
        // Comme la demande par e-mail : aucun compte à l'émission (il naîtra à la consommation).
        self::assertNull($this->em()->getRepository(User::class)->findOneBy([
            'email' => 'newcomer@social-signup.test',
        ]));
    }

    public function testTokenSignedByAnotherKeyIsRejectedWithoutSideEffect(): void
    {
        $forged = OidcTestKeys::sign([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'exp' => time() + 300,
            'email' => 'attacker@evil.test',
            'email_verified' => true,
        ], OidcTestKeys::foreignPrivateKey());

        $this->request('POST', '/v1/auth/social/magic-link', null, [
            'provider' => 'google',
            'idToken' => $forged,
        ]);

        self::assertResponseStatusCodeSame(401);
        // Ni lien, ni compte : un jeton non prouvé ne laisse AUCUNE trace.
        self::assertNull($this->em()->getRepository(MagicLinkToken::class)->findOneBy(['email' => 'attacker@evil.test']));
        self::assertNull($this->em()->getRepository(User::class)->findOneBy(['email' => 'attacker@evil.test']));
    }

    public function testUnverifiedEmailIsRejected(): void
    {
        $this->request('POST', '/v1/auth/social/magic-link', null, [
            'provider' => 'google',
            'idToken' => $this->idToken(['email' => 'unverified@social-signup.test', 'email_verified' => false]),
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertNull($this->em()->getRepository(MagicLinkToken::class)->findOneBy(['email' => 'unverified@social-signup.test']));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $this->request('POST', '/v1/auth/social/magic-link', null, [
            'provider' => 'google',
            'idToken' => $this->idToken(['email' => 'expired@social-signup.test', 'exp' => time() - 60, 'iat' => time() - 300]),
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertNull($this->em()->getRepository(MagicLinkToken::class)->findOneBy(['email' => 'expired@social-signup.test']));
    }

    public function testUnknownProviderIsRejectedByValidation(): void
    {
        $this->request('POST', '/v1/auth/social/magic-link', null, [
            'provider' => 'facebook',
            'idToken' => $this->idToken(),
        ]);

        // Liste fermée : un fournisseur hors périmètre est refusé par la validation (422), pas 401.
        self::assertResponseStatusCodeSame(422);
    }
}
