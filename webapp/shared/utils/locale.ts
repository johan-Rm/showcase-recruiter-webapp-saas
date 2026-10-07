import type { LocaleCode, LocaleInfo } from '../types/i18n'

// Registre unique des langues : consommé par le nuxt.config, l'app, le serveur et les tests.
// Pur (aucun import framework) — c'est ce qui permet au nuxt.config de l'importer au build.
// La *résolution* de la locale reste arbitrée par @nuxtjs/i18n seul (cf. webapp/README.md).

export const FALLBACK_LOCALE: LocaleCode = 'fr'

export const AVAILABLE_LOCALES = {
  fr: { code: 'fr', iso: 'fr-FR', name: 'Français', dir: 'ltr', file: 'fr.yaml' },
  en: { code: 'en', iso: 'en-GB', name: 'English', dir: 'ltr', file: 'en.yaml' },
  ar: { code: 'ar', iso: 'ar-MA', name: 'العربية', dir: 'rtl', file: 'ar.yaml' },
} as const satisfies Record<LocaleCode, LocaleInfo>

/** Ordre stable — celui du sélecteur de langue. */
export const SUPPORTED_LOCALES: readonly LocaleInfo[] = Object.values(AVAILABLE_LOCALES)

export const isLocaleCode = (value: unknown): value is LocaleCode =>
  typeof value === 'string' && value in AVAILABLE_LOCALES

/** `fr-FR` → `fr`, `AR` → `ar`, inconnu ou vide → fallback. */
export const normalizeLocale = (value: string | null | undefined): LocaleCode => {
  const base = value?.split('-').at(0)?.toLowerCase()

  return isLocaleCode(base) ? base : FALLBACK_LOCALE
}
