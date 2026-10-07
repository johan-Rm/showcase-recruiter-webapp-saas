// Résolution d'un nom de route Nuxt vers l'entrée de navigation qui le porte. Isolé du composable
// parce que c'est la seule part testable hors runtime — et c'est ici qu'une table de libellés
// incomplète laisse un écran sans fil d'ariane, sans que rien ne le signale.

/** Suffixe ajouté par @nuxtjs/i18n à chaque nom de route (`candidats___fr` → `candidats`). */
export const stripLocaleSuffix = (routeName: string): string => routeName.replace(/___[a-z-]+$/, '')

/**
 * Nom de route ramené à l'entrée de navigation qui le porte : la route elle-même si elle est
 * déclarée, sinon sa section parente en retirant les segments de fin (`candidats-id` →
 * `candidats`). `null` si rien ne correspond — une route hors navigation n'est pas une erreur.
 */
export const resolveNavRoute = (
  routeName: unknown,
  navRoutes: readonly string[],
): string | null => {
  if (typeof routeName !== 'string') return null

  let candidate = stripLocaleSuffix(routeName)
  while (candidate) {
    if (navRoutes.includes(candidate)) return candidate

    const cut = candidate.lastIndexOf('-')
    if (cut === -1) return null
    candidate = candidate.slice(0, cut)
  }

  return null
}
