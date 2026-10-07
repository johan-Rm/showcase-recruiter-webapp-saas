<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\WhatsAppIdentifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Jarvi ne fournit pas de numéro WhatsApp : il marque un téléphone d'un JID dont la partie locale
 * EST le numéro. Ce qui se dérive de ce JID sort donc au dévoilement, jamais avant.
 */
final class WhatsAppIdentifierTest extends TestCase
{
    #[DataProvider('identifierProvider')]
    public function testToE164(string $jid, ?string $expected): void
    {
        self::assertSame($expected, WhatsAppIdentifier::toE164($jid));
    }

    /** @return iterable<string, array{string, ?string}> */
    public static function identifierProvider(): iterable
    {
        yield 'JID standard' => ['212600000000@s.whatsapp.net', '+212600000000'];
        yield 'JID suffixé du terminal' => ['212600000000:12@s.whatsapp.net', '+212600000000'];
        yield 'domaine historique' => ['33600000000@c.us', '+33600000000'];
        yield 'sans domaine' => ['212600000000', null];
        yield 'partie locale non numérique' => ['groupe-maroc@g.us', null];
        yield 'trop court pour un numéro' => ['12345@s.whatsapp.net', null];
        yield 'au-delà de la borne E.164' => ['1234567890123456@s.whatsapp.net', null];
        yield 'vide' => ['', null];
    }

    public function testFromContactsReturnsFirstReachableNumber(): void
    {
        $number = WhatsAppIdentifier::fromContacts([
            ['type' => 'email', 'value' => 'candidat@example.com', 'source' => 'LINKEDIN'],
            ['type' => 'phone', 'value' => '+33 6 00 00 00 00', 'source' => 'RESUME', 'whatsappIdentifier' => null],
            ['type' => 'phone', 'value' => '+212 600000000', 'source' => 'MANUAL', 'whatsappIdentifier' => '212600000000@s.whatsapp.net'],
        ]);

        self::assertSame('+212600000000', $number);
    }

    /** Les contacts ingérés avant l'ajout du champ n'ont pas la clé : absence ≠ erreur. */
    public function testFromContactsWithoutWhatsAppData(): void
    {
        self::assertNull(WhatsAppIdentifier::fromContacts([
            ['type' => 'phone', 'value' => '+33 6 00 00 00 00', 'source' => 'RESUME'],
        ]));
    }
}
