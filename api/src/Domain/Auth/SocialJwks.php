<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Enum\SocialProvider;

/**
 * Fournit le JWKS (jeu de clés publiques) d'un fournisseur OIDC, sous sa forme décodée
 * (`['keys' => [...]]`). Abstrait derrière une interface pour un seul but : les tests substituent un
 * JWKS de test sans réseau, là où la prod le récupère chez Google / LinkedIn.
 */
interface SocialJwks
{
    /**
     * JWKS brut (`{keys: [...]}`) tel que renvoyé par le fournisseur. La FORME fine n'est pas garantie
     * ici : `JWK::parseKeySet` la valide en aval et rejette un jeu de clés malformé.
     *
     * @return array<string, mixed>
     */
    public function forProvider(SocialProvider $provider): array;
}
