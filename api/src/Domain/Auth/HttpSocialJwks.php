<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Enum\SocialProvider;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Récupère le JWKS d'un fournisseur par HTTP et le met en cache. Les fournisseurs FONT TOURNER leurs
 * clés : on ne les fige donc pas, on les rafraîchit à l'expiration du cache. Le TTL est un compromis —
 * assez long pour ne pas frapper le fournisseur à chaque connexion, assez court pour prendre une
 * rotation en moins d'une heure.
 *
 * `$jwksUris` est une carte fermée `provider => URL` : une URL absente signifie « fournisseur non
 * configuré », rejeté sans appel réseau.
 */
final readonly class HttpSocialJwks implements SocialJwks
{
    /**
     * @param array<string, string> $jwksUris
     */
    public function __construct(
        private HttpClientInterface $http,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private array $jwksUris,
        private int $ttlSeconds = 3600,
    ) {
    }

    public function forProvider(SocialProvider $provider): array
    {
        $uri = $this->jwksUris[$provider->value] ?? '';
        if ('' === $uri) {
            throw new SocialIdentityException(\sprintf('Fournisseur social non configuré : %s.', $provider->value));
        }

        try {
            // `toArray()` lève déjà si la réponse n'est pas du JSON ; la forme fine du JWKS est validée
            // en aval par `JWK::parseKeySet`. Inutile de re-garder la structure ici.
            return $this->cache->get(
                'oidc_jwks_'.$provider->value,
                function (ItemInterface $item) use ($uri): array {
                    $item->expiresAfter($this->ttlSeconds);

                    return $this->http->request('GET', $uri)->toArray();
                },
            );
        } catch (Throwable $e) {
            // Le fournisseur injoignable ou une réponse illisible ne doit pas fuiter en 500 : c'est un
            // échec de vérification comme un autre. On TRACE (diagnostic) mais on ne divulgue rien.
            $this->logger->warning('Récupération du JWKS échouée.', ['provider' => $provider->value, 'error' => $e->getMessage()]);

            throw new SocialIdentityException('JWKS indisponible.', 0, $e);
        }
    }
}
