<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Firebase\JWT\JWT;
use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * Fabrique d'`id_token` de test : signe des jetons avec la clé privée de test et publie le JWKS
 * correspondant (dérivé de la clé publique). Aucun secret réel — la paire vit dans
 * `tests/Resources/oidc/` et ne sert qu'aux tests de vérification sociale.
 *
 * Le JWKS est CALCULÉ depuis la clé publique (modulus/exposant), il ne peut donc pas diverger de la
 * clé qui signe : signer et vérifier partent de la même paire, par construction.
 */
final class OidcTestKeys
{
    public const KID = 'test-key-1';

    private const DIR = __DIR__.'/../Resources/oidc';

    public static function sign(array $claims, ?string $privateKey = null, string $kid = self::KID): string
    {
        return JWT::encode($claims, $privateKey ?? self::privateKey(), 'RS256', $kid);
    }

    /**
     * JWKS au format Google / LinkedIn : une clé RSA de signature, `alg` RS256, repérée par `kid`.
     *
     * @return array{keys: list<array<string, string>>}
     */
    public static function jwks(string $kid = self::KID): array
    {
        $details = openssl_pkey_get_details(self::publicResource());
        if (false === $details || !isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new RuntimeException('Clé publique de test illisible.');
        }

        return ['keys' => [[
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $kid,
            'n' => self::base64Url($details['rsa']['n']),
            'e' => self::base64Url($details['rsa']['e']),
        ]]];
    }

    public static function privateKey(): string
    {
        return self::read('test-private.pem');
    }

    /**
     * Une seconde clé privée, jetable, pour forger un jeton « mal signé » : signé par une clé dont le
     * JWKS de test n'a pas la publique.
     */
    public static function foreignPrivateKey(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => \OPENSSL_KEYTYPE_RSA]);
        if (false === $key) {
            throw new RuntimeException('Génération de clé étrangère impossible.');
        }
        openssl_pkey_export($key, $pem);

        return $pem;
    }

    private static function publicResource(): OpenSSLAsymmetricKey
    {
        $resource = openssl_pkey_get_public(self::read('test-public.pem'));
        if (false === $resource) {
            throw new RuntimeException('Clé publique de test illisible.');
        }

        return $resource;
    }

    private static function read(string $file): string
    {
        $content = file_get_contents(self::DIR.'/'.$file);
        if (false === $content) {
            throw new RuntimeException(\sprintf('Fixture OIDC absente : %s.', $file));
        }

        return $content;
    }

    private static function base64Url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
