import { ISO_3166_1_ALPHA2 } from '#shared/utils/countries'

import type { SelectOption } from '#shared/types/select'

/**
 * Liste des pays pour l'autocomplétion : chaque code ISO 3166-1 alpha-2 associé à son nom LOCALISÉ,
 * triée par nom. `Intl.DisplayNames` fournit les noms dans la langue courante (fr / en / ar) — aucune
 * table de traduction à tenir. La valeur stockée reste le code (« MA »), le libellé n'est qu'affiché.
 */
export const useCountries = () => {
  const { current } = useLocale()

  return computed<SelectOption[]>(() => {
    const display = new Intl.DisplayNames([current.value], { type: 'region' })

    return ISO_3166_1_ALPHA2.map((code) => ({ value: code, label: display.of(code) ?? code })).sort(
      (a, b) => a.label.localeCompare(b.label, current.value),
    )
  })
}
