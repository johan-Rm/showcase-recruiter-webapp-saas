<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\JarviContacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Jarvi donne les coordonnées en tableaux, doublons compris : même e-mail sous deux sources, même
 * numéro en `06…`, `00212…` et `+212…`. Le modèle showcase-recruiter-webapp-saas est mono-valué — il faut trancher.
 */
final class JarviContactsTest extends TestCase
{
    public function testEmailPrefersManualOverResumeAndLinkedin(): void
    {
        self::assertSame('manuel@example.com', JarviContacts::email([
            ['type' => 'email', 'value' => 'linkedin@example.com', 'source' => 'LINKEDIN'],
            ['type' => 'email', 'value' => 'manuel@example.com', 'source' => 'MANUAL'],
            ['type' => 'email', 'value' => 'cv@example.com', 'source' => 'RESUME'],
        ]));
    }

    /** À source égale, la première rencontrée : la réplica ne porte pas de date de saisie. */
    public function testEmailKeepsFirstOnEqualSource(): void
    {
        self::assertSame('premier@example.com', JarviContacts::email([
            ['type' => 'email', 'value' => 'premier@example.com', 'source' => 'RESUME'],
            ['type' => 'email', 'value' => 'second@example.com', 'source' => 'RESUME'],
        ]));
    }

    /** Une source inconnue ne doit pas primer sur les sources connues — elle passe en dernier. */
    public function testUnknownSourceLosesAgainstKnownOnes(): void
    {
        self::assertSame('cv@example.com', JarviContacts::email([
            ['type' => 'email', 'value' => 'inconnu@example.com', 'source' => 'IMPORT_X'],
            ['type' => 'email', 'value' => 'cv@example.com', 'source' => 'RESUME'],
        ]));
    }

    public function testEmailIgnoresPhonesAndEmptyValues(): void
    {
        self::assertNull(JarviContacts::email([
            ['type' => 'phone', 'value' => '+212600000000', 'source' => 'MANUAL'],
            ['type' => 'email', 'value' => '', 'source' => 'MANUAL'],
        ]));
    }

    #[DataProvider('phoneProvider')]
    public function testPhoneNormalization(string $stored, string $expected): void
    {
        $phone = JarviContacts::phone([['type' => 'phone', 'value' => $stored, 'source' => 'RESUME']]);

        self::assertSame($expected, $phone);
    }

    /** @return iterable<string, array{string, string}> */
    public static function phoneProvider(): iterable
    {
        // Formes relevées sur le vivier : 36 numéros à espaces, 5 à points, 1 à tiret, 3 en `00…`.
        yield 'espaces retirés' => ['+212 6 19 81 39 59', '+212619813959'];
        yield 'points et tirets retirés' => ['+33 6.99.53-56.48', '+33699535648'];
        yield 'préfixe 00 ramené à +' => ['00212619813959', '+212619813959'];
        // Sans pays, compléter serait une invention : on affiche ce que Jarvi détient.
        yield 'numéro national laissé tel quel' => ['0603584617', '0603584617'];
        // Notation jamais rencontrée : intouchée plutôt que transformée en numéro faux.
        yield 'indicatif de transit entre parenthèses laissé tel quel' => ['+33(0)699535648', '+33(0)699535648'];
    }

    public function testLinkedinUrlComesFromRawPayload(): void
    {
        self::assertSame(
            'https://www.linkedin.com/in/exemple',
            JarviContacts::linkedinUrl(['profileUrl' => 'https://www.linkedin.com/in/exemple']),
        );
        self::assertNull(JarviContacts::linkedinUrl(null));
        self::assertNull(JarviContacts::linkedinUrl(['profileUrl' => '']));
    }
}
