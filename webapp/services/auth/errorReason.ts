/**
 * Descend un chemin de clés dans une valeur inconnue.
 *
 * Parcouru à la main plutôt qu'assené par une assertion de type : c'est une donnée externe, et un
 * proxy ou une passerelle peut renvoyer tout autre chose qu'un corps d'erreur applicatif.
 */
export const readErrorString = (value: unknown, path: readonly string[]): string | null => {
  let current: unknown = value

  for (const key of path) {
    if (null === current || 'object' !== typeof current || !(key in current)) {
      return null
    }

    current = (current as Record<string, unknown>)[key]
  }

  return 'string' === typeof current ? current : null
}
