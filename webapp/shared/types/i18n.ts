export type LocaleCode = 'fr' | 'en' | 'ar'

export interface LocaleInfo {
  code: LocaleCode
  /** Balise BCP-47 posée sur `<html lang>`. */
  iso: string
  /** Libellé natif, affiché dans le sélecteur. */
  name: string
  dir: 'ltr' | 'rtl'
  /** Fichier de messages dans `base/i18n/locales/`. */
  file: string
}
