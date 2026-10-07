<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\JarviClient;
use App\Domain\Jarvi\JarviProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Traversée GraphQL → JarviProfile des champs ajoutés au contrat de lecture : la photo (URL
 * publique seule) et l'identifiant WhatsApp porté par le téléphone.
 */
final class JarviClientMappingTest extends TestCase
{
    private const string PROJECT_ID = '00000000-0000-4000-8000-000000000001';
    private const string PHOTO_URL = 'https://qimsyaozqntinmrokopq.storage.eu-west-2.nhost.run/v1/files/c5243c8c-1f8a-4306-87e0-12977ed2c2a7';

    public function testProfileCarriesPhotoUrlAndWhatsAppIdentifier(): void
    {
        $profiles = $this->profilesFrom([[
            'id' => '019f3213-dbfa-774e-a3e0-de6f265c6d13',
            'firstName' => 'Yassine',
            'lastName' => 'Amrani',
            'updatedAt' => '2026-07-05T11:39:50+00:00',
            'image' => ['publicUrl' => self::PHOTO_URL, '__typename' => 'images'],
            'phoneNumbers' => [[
                'number' => '+212 600000000',
                'type' => 'MOBILE',
                'source' => 'RESUME',
                'whatsappIdentifier' => '212600000000@s.whatsapp.net',
            ]],
        ]]);

        self::assertCount(1, $profiles);
        self::assertSame(self::PHOTO_URL, $profiles[0]->photoUrl);
        self::assertSame([[
            'type' => 'phone',
            'value' => '+212 600000000',
            'source' => 'RESUME',
            'whatsappIdentifier' => '212600000000@s.whatsapp.net',
        ]], $profiles[0]->contacts);
    }

    /** Photo absente (14 % du vivier) et téléphone hors WhatsApp : deux `null`, pas deux erreurs. */
    public function testProfileWithoutPhotoNorWhatsApp(): void
    {
        $profiles = $this->profilesFrom([[
            'id' => '019f3213-dbfa-774e-a3e0-de6f265c6d14',
            'firstName' => 'Sans',
            'lastName' => 'Photo',
            'updatedAt' => '2026-07-05T11:39:50+00:00',
            'image' => null,
            'phoneNumbers' => [[
                'number' => '+33 600000000',
                'type' => 'MOBILE',
                'source' => 'MANUAL',
                'whatsappIdentifier' => null,
            ]],
        ]]);

        self::assertNull($profiles[0]->photoUrl);
        self::assertNull($profiles[0]->contacts[0]['whatsappIdentifier']);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<JarviProfile>
     */
    private function profilesFrom(array $rows): array
    {
        $client = new JarviClient(
            new MockHttpClient(new MockResponse(json_encode(['data' => ['profiles' => $rows]], \JSON_THROW_ON_ERROR))),
            self::PROJECT_ID,
        );

        // Le client rend un flux : ces tests de mapping le matérialisent, l'ingestion non.
        return array_values(iterator_to_array($client->fetchProfiles()));
    }
}
