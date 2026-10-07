<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Enum\LanguageProficiency;

/**
 * Langues parlées d'un profil, extraites des `locales` Jarvi (task 114).
 *
 * Les langues forment un **vocabulaire fermé** (29 valeurs pour ~3 100 occurrences) : aucun
 * regroupement ni LLM n'est nécessaire, une normalisation suffit.
 *
 * Un même code peut apparaître plusieurs fois sur un profil : on conserve alors le **niveau le plus
 * élevé**, un niveau renseigné primant toujours sur un niveau absent.
 */
final class SpokenLanguageExtractor
{
    /** Code ISO factice présent dans les données réelles (21 occurrences) : ce n'est pas une langue. */
    private const string UNKNOWN_CODE = 'xx';

    /**
     * @param list<array<string, mixed>> $locales `locales` Jarvi (language, country, proficiency)
     *
     * @return array<string, LanguageProficiency|null> code langue → niveau (null si non renseigné)
     */
    public static function fromLocales(array $locales): array
    {
        $languages = [];
        foreach ($locales as $locale) {
            $code = mb_strtolower(trim((string) ($locale['language'] ?? '')));
            if ('' === $code || self::UNKNOWN_CODE === $code) {
                continue;
            }

            $level = LanguageProficiency::fromJarvi($locale['proficiency'] ?? null);
            if (!\array_key_exists($code, $languages) || self::outranks($level, $languages[$code])) {
                $languages[$code] = $level;
            }
        }

        return $languages;
    }

    private static function outranks(?LanguageProficiency $candidate, ?LanguageProficiency $current): bool
    {
        if (!$candidate instanceof LanguageProficiency) {
            return false;
        }

        return !$current instanceof LanguageProficiency || $candidate->rank() > $current->rank();
    }
}
