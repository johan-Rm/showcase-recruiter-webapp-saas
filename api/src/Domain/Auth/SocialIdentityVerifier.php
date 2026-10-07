<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Enum\SocialProvider;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Throwable;

/**
 * Prouve l'`id_token` OIDC d'un fournisseur et n'en retient qu'UNE chose : l'e-mail vérifié. Le social
 * login ne fait rien d'autre — pas de session, pas de rôle, pas de compte (cf. tâche 081). C'est le
 * magic link, émis ensuite à cet e-mail, qui reste l'unique porte d'authentification sans mot de passe.
 *
 * **On ne fait jamais confiance à un e-mail annoncé.** On vérifie la signature de l'`id_token` contre
 * le JWKS du fournisseur, puis l'émetteur, l'audience (notre client_id) et l'expiration ; enfin que le
 * fournisseur atteste lui-même l'e-mail (`email_verified`). Sans ce dernier point, un fournisseur qui
 * laisse déclarer une adresse non prouvée permettrait de recevoir le magic link de quelqu'un d'autre.
 *
 * `RS256` est imposé au parsing du JWKS : les clés reconstruites sont RSA + RS256, ce qui ferme
 * l'attaque de confusion d'algorithme (un `alg: none`, ou un HS256 utilisant la clé publique comme
 * secret partagé, ne peut pas être validé par une clé RSA).
 */
final readonly class SocialIdentityVerifier
{
    /**
     * @param array<string, array{issuer: string, audience: string}> $providers
     */
    public function __construct(
        private SocialJwks $jwks,
        private array $providers,
    ) {
    }

    /**
     * @throws SocialIdentityException si l'`id_token` n'est pas prouvable ou l'e-mail non vérifié
     */
    public function verifiedEmail(SocialProvider $provider, string $idToken): string
    {
        $config = $this->providers[$provider->value] ?? null;
        if (null === $config || '' === $config['audience']) {
            throw new SocialIdentityException(\sprintf('Fournisseur social non configuré : %s.', $provider->value));
        }

        try {
            // parseKeySet impose RS256 par défaut : verrou anti-confusion d'algorithme (cf. en-tête).
            $keys = JWK::parseKeySet($this->jwks->forProvider($provider), 'RS256');
            // JWT::decode vérifie la signature ET l'expiration (`exp`), et rejette un `alg` non conforme.
            $claims = JWT::decode($idToken, $keys);
        } catch (SocialIdentityException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SocialIdentityException('Jeton social invalide.', 0, $e);
        }

        if ((string) ($claims->iss ?? '') !== $config['issuer']) {
            throw new SocialIdentityException('Émetteur inattendu.');
        }

        if (!$this->audienceMatches($claims->aud ?? null, $config['audience'])) {
            throw new SocialIdentityException('Audience inattendue.');
        }

        if (true !== $this->normalizeBool($claims->email_verified ?? null)) {
            throw new SocialIdentityException('E-mail non vérifié par le fournisseur.');
        }

        $email = strtolower(trim((string) ($claims->email ?? '')));
        if ('' === $email) {
            throw new SocialIdentityException('E-mail absent du jeton.');
        }

        return $email;
    }

    /**
     * `aud` peut être une chaîne unique ou une liste (spec JWT) : notre client_id doit y figurer.
     */
    private function audienceMatches(mixed $aud, string $expected): bool
    {
        if (\is_string($aud)) {
            return $aud === $expected;
        }

        return \is_array($aud) && \in_array($expected, $aud, true);
    }

    /**
     * `email_verified` arrive booléen chez Google, parfois en chaîne `"true"` ailleurs. On n'accepte
     * QUE le vrai explicite — tout le reste (absent, `false`, `"false"`, null) vaut non vérifié.
     */
    private function normalizeBool(mixed $value): bool
    {
        return true === $value || 'true' === $value;
    }
}
