<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Auth\SocialJwks;
use App\Enum\SocialProvider;

/**
 * Variante DI de {@see StubSocialJwks} : sans argument de constructeur, elle se câble en test pour
 * remplacer `HttpSocialJwks`. Le verifier fait alors confiance à la clé de test au lieu d'appeler
 * Google — aucun réseau dans les tests fonctionnels.
 */
final class TestSocialJwks implements SocialJwks
{
    public function forProvider(SocialProvider $provider): array
    {
        return OidcTestKeys::jwks();
    }
}
