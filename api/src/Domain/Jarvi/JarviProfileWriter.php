<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Implémentation HTTP d'écriture profil : `POST /profiles` (upsert) sur le client
 * scopé `jarvi.client` (base_uri + X-API-KEY, cf. jarvi.yaml). Clé privée côté api/ uniquement.
 */
final readonly class JarviProfileWriter implements JarviProfileWriterInterface
{
    public function __construct(
        private HttpClientInterface $jarviClient,
    ) {
    }

    public function upsert(JarviUpsertProfile $profile): string
    {
        $body = [
            'id' => $profile->id,
            'externalId' => $profile->externalId,
            'firstName' => $profile->firstName,
            'lastName' => $profile->lastName,
        ];
        if (null !== $profile->linkedinUrl && '' !== $profile->linkedinUrl) {
            $body['linkedinUrl'] = $profile->linkedinUrl;
        }
        if (null !== $profile->location && '' !== $profile->location) {
            $body['location'] = $profile->location;
        }
        if (null !== $profile->projectId && '' !== $profile->projectId) {
            $body['projectId'] = $profile->projectId;
        }
        if (null !== $profile->emailAddresses && '' !== $profile->emailAddresses) {
            $body['emailAddresses'] = $profile->emailAddresses;
        }

        $payload = $this->jarviClient->request('POST', 'profiles', ['json' => $body])->toArray();

        $profileId = $payload['profileId'] ?? null;
        if (!\is_string($profileId) || '' === $profileId) {
            throw new JarviWriteException('Réponse Jarvi inattendue : profileId manquant.');
        }

        return $profileId;
    }
}
