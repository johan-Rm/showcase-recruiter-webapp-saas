<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Auth\SocialIdentityException;
use App\Domain\Auth\SocialIdentityVerifier;
use App\Enum\SocialProvider;
use App\Tests\Support\OidcTestKeys;
use App\Tests\Support\StubSocialJwks;
use PHPUnit\Framework\TestCase;

/**
 * Cœur sensible : la porte qui décide à QUEL e-mail un magic link sera envoyé. Un jeton accepté à
 * tort ferait envoyer ce lien à l'adresse d'un tiers. On prouve donc qu'UN SEUL cas passe — jeton
 * bien signé, bon émetteur, bonne audience, non expiré, e-mail vérifié — et que chaque écart échoue.
 */
final class SocialIdentityVerifierTest extends TestCase
{
    private const ISSUER = 'https://accounts.google.com';
    private const AUDIENCE = 'test-client-id.apps.googleusercontent.com';

    private function verifier(): SocialIdentityVerifier
    {
        return new SocialIdentityVerifier(
            new StubSocialJwks(OidcTestKeys::jwks()),
            ['google' => ['issuer' => self::ISSUER, 'audience' => self::AUDIENCE]],
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function token(array $overrides = []): string
    {
        return OidcTestKeys::sign([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'exp' => time() + 300,
            'iat' => time(),
            'email' => 'Recruteur@Entreprise-ABC.test',
            'email_verified' => true,
            ...$overrides,
        ]);
    }

    public function testValidTokenYieldsNormalizedVerifiedEmail(): void
    {
        // L'e-mail est normalisé (minuscules, trim) comme partout ailleurs dans le domaine Auth.
        self::assertSame(
            'recruteur@entreprise-abc.test',
            $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $this->token()),
        );
    }

    public function testAudienceInListIsAccepted(): void
    {
        // `aud` peut être une liste (spec JWT) : notre client_id doit simplement y figurer.
        $token = $this->token(['aud' => ['autre-client', self::AUDIENCE]]);

        self::assertSame('recruteur@entreprise-abc.test', $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $token));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $this->expectException(SocialIdentityException::class);
        $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $this->token(['exp' => time() - 60, 'iat' => time() - 300]));
    }

    public function testWrongAudienceIsRejected(): void
    {
        $this->expectException(SocialIdentityException::class);
        $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $this->token(['aud' => 'someone-elses-client-id']));
    }

    public function testWrongIssuerIsRejected(): void
    {
        $this->expectException(SocialIdentityException::class);
        $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $this->token(['iss' => 'https://evil.example.com']));
    }

    public function testUnverifiedEmailIsRejected(): void
    {
        $this->expectException(SocialIdentityException::class);
        $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $this->token(['email_verified' => false]));
    }

    public function testTokenSignedByAnotherKeyIsRejected(): void
    {
        // Signé par une clé dont le JWKS de test n'a pas la publique : signature invalide.
        $forged = OidcTestKeys::sign([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'exp' => time() + 300,
            'email' => 'attacker@evil.test',
            'email_verified' => true,
        ], OidcTestKeys::foreignPrivateKey());

        $this->expectException(SocialIdentityException::class);
        $this->verifier()->verifiedEmail(SocialProvider::GOOGLE, $forged);
    }

    public function testUnconfiguredProviderIsRejected(): void
    {
        // LinkedIn absent de la config (audience non renseignée) → rejeté sans même toucher le jeton.
        $this->expectException(SocialIdentityException::class);
        $this->verifier()->verifiedEmail(SocialProvider::LINKEDIN, $this->token());
    }
}
