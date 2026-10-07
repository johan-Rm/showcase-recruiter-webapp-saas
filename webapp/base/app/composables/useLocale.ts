import type { LocaleCode } from '#shared/types/i18n'
import { AVAILABLE_LOCALES, SUPPORTED_LOCALES, isLocaleCode } from '#shared/utils/locale'

// Wrapper unique de @nuxtjs/i18n : le reste de l'app dépend de ce contrat, jamais du module.
// `$t` fait exception — c'est la raison d'être du module, utilisé tel quel dans les templates.
//
// On passe par `useNuxtApp().$i18n` et NON par `useI18n()` : ce dernier vient de vue-i18n et
// exige un contexte `setup()` de composant, donc il casse dans un route middleware.
export const useLocale = () => {
  const { $i18n } = useNuxtApp()
  const localePath = useLocalePath()

  const current = computed<LocaleCode>(() => $i18n.locale.value as LocaleCode)
  const dir = computed(() => AVAILABLE_LOCALES[current.value].dir)

  // Accepte une valeur brute (event DOM) et la valide — pas de liste de langues dans l'UI.
  const setLocale = async (value: string): Promise<void> => {
    if (isLocaleCode(value)) {
      await $i18n.setLocale(value)
    }
  }

  // `t` pour les SCRIPTS. Les templates ont `$t`, mais une option de composant — libellé d'onglet,
  // en-tête de colonne — se compose en JavaScript. Sans lui ici, chaque écran rouvrirait le module
  // pour son propre compte, ce que ce wrapper existe précisément pour éviter.
  const t = (key: string, params?: Record<string, unknown>): string => $i18n.t(key, params ?? {})

  return { current, dir, locales: SUPPORTED_LOCALES, setLocale, localePath, t }
}
