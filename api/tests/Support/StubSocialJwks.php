<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Auth\SocialJwks;
use App\Enum\SocialProvider;

/**
 * JWKS de test : renvoie un jeu de clés fixé, sans réseau. Substitué à `HttpSocialJwks` dans les
 * tests pour que le verifier fasse confiance à notre clé de test plutôt qu'à celle de Google.
 */
final class StubSocialJwks implements SocialJwks
{
    /**
     * @param array{keys: list<array<string, mixed>>} $jwks
     */
    public function __construct(private readonly array $jwks)
    {
    }

    public function forProvider(SocialProvider $provider): array
    {
        return $this->jwks;
    }
}
