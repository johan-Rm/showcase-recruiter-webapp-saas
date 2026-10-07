<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\VocabularyImporter;
use App\Enum\VocabularyStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Statut lu depuis le fichier versionné (task 113). C'est le mécanisme qui rend la validation
 * humaine possible sans écran : on passe `"statut": "VALIDE"` dans `taxonomy.json`, l'import le
 * porte en base, et la décision se relit en `git diff`.
 *
 * La règle de repli est la partie sensible : **une valeur absente, vide ou inconnue retombe sur
 * CANDIDAT, jamais sur VALIDE**. On n'accorde pas de confiance par défaut — c'est l'invariant du
 * projet, et un typo dans le fichier ne doit pas promouvoir un concept en silence.
 */
final class VocabularyStatusFromFileTest extends TestCase
{
    /** @return list<array{mixed, VocabularyStatus}> */
    public static function statusProvider(): array
    {
        return [
            ['VALIDE', VocabularyStatus::VALIDE],
            ['valide', VocabularyStatus::VALIDE],
            ['DEPRECIE', VocabularyStatus::DEPRECIE],
            ['CANDIDAT', VocabularyStatus::CANDIDAT],
            ['candidat', VocabularyStatus::CANDIDAT],
            // Repli : rien ne doit promouvoir accidentellement.
            [null, VocabularyStatus::CANDIDAT],
            ['', VocabularyStatus::CANDIDAT],
            ['n’importe quoi', VocabularyStatus::CANDIDAT],
            [42, VocabularyStatus::CANDIDAT],
            [true, VocabularyStatus::CANDIDAT],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testStatusIsReadFromFileAndNeverPromotesByAccident(mixed $raw, VocabularyStatus $expected): void
    {
        $status = (new ReflectionMethod(VocabularyImporter::class, 'status'))->invoke(null, $raw);

        self::assertSame($expected, $status);
    }
}
