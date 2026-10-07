<?php

declare(strict_types=1);

namespace App\Translation;

use App\Enum\OriginLocale;

/**
 * Résolution de la locale de lecture (cf. i18n.md) : locales activées FR/AR/EN, source = FR
 * (fallback). La locale demandée vient de ?locale ; si invalide/absente → locale source.
 */
final class LocaleResolver
{
    /** @var list<string> */
    public const array ENABLED = ['fr', 'ar', 'en'];

    public const string SOURCE = 'fr';

    /** Locale demandée normalisée (toujours une locale activée). */
    public function requested(?string $raw): OriginLocale
    {
        $value = strtolower(trim((string) $raw));

        return \in_array($value, self::ENABLED, true)
            ? OriginLocale::from($value)
            : OriginLocale::from(self::SOURCE);
    }

    public function source(): OriginLocale
    {
        return OriginLocale::from(self::SOURCE);
    }
}
