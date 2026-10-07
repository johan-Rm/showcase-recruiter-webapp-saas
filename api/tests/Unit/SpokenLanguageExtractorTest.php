<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\SpokenLanguageExtractor;
use App\Enum\LanguageProficiency;
use PHPUnit\Framework\TestCase;

/**
 * Extraction des langues parlées (task 114). Les cas couverts viennent des données réelles du
 * vivier : niveau souvent absent (~35 % des entrées), variante `PROFESSIONAL` hors standard, et
 * code factice `xx`.
 */
final class SpokenLanguageExtractorTest extends TestCase
{
    public function testExtractsLanguagesWithTheirLevel(): void
    {
        $languages = SpokenLanguageExtractor::fromLocales([
            ['language' => 'fr', 'proficiency' => 'NATIVE_OR_BILINGUAL'],
            ['language' => 'en', 'proficiency' => 'PROFESSIONAL_WORKING'],
        ]);

        self::assertSame(
            ['fr' => LanguageProficiency::NATIVE_OR_BILINGUAL, 'en' => LanguageProficiency::PROFESSIONAL_WORKING],
            $languages,
        );
    }

    public function testKeepsLanguageWhenLevelIsMissing(): void
    {
        $languages = SpokenLanguageExtractor::fromLocales([
            ['language' => 'es', 'proficiency' => null],
        ]);

        self::assertArrayHasKey('es', $languages);
        self::assertNull($languages['es'], 'le niveau absent ne doit pas être supposé');
    }

    public function testMapsNonStandardProfessionalVariant(): void
    {
        $languages = SpokenLanguageExtractor::fromLocales([
            ['language' => 'nl', 'proficiency' => 'PROFESSIONAL'],
        ]);

        self::assertSame(LanguageProficiency::PROFESSIONAL_WORKING, $languages['nl']);
    }

    public function testIgnoresPlaceholderAndEmptyCodes(): void
    {
        $languages = SpokenLanguageExtractor::fromLocales([
            ['language' => 'xx', 'proficiency' => 'ELEMENTARY'],
            ['language' => '', 'proficiency' => 'ELEMENTARY'],
        ]);

        self::assertSame([], $languages);
    }

    public function testKeepsHighestLevelOnDuplicateLanguage(): void
    {
        $languages = SpokenLanguageExtractor::fromLocales([
            ['language' => 'ar', 'proficiency' => 'ELEMENTARY'],
            ['language' => 'ar', 'proficiency' => 'NATIVE_OR_BILINGUAL'],
            ['language' => 'ar', 'proficiency' => null],
        ]);

        self::assertSame(LanguageProficiency::NATIVE_OR_BILINGUAL, $languages['ar']);
    }

    public function testAtLeastReturnsTheAcceptableLevels(): void
    {
        self::assertSame(
            [LanguageProficiency::FULL_PROFESSIONAL, LanguageProficiency::NATIVE_OR_BILINGUAL],
            LanguageProficiency::atLeast(LanguageProficiency::FULL_PROFESSIONAL),
        );
    }
}
