const routeKey = (name: unknown): string => String(name ?? '').replace(/___[a-z]{2}$/, '')

const normalizeParams = (params: Record<string, string | string[]>): Record<string, string> =>
  Object.fromEntries(
    Object.entries(params).map(([key, value]) => [
      key,
      Array.isArray(value) ? (value[0] ?? '') : value,
    ]),
  )

/**
 * Préchargement prédictif N+1 : anticipe la donnée de la navigation probable depuis la
 * route courante (ex. les fiches des candidats visibles → clic instantané). Déclenché à
 * `passiveReady` — l'utilisateur est engagé, le rendu utile et le préchargement de base sont déjà
 * passés. Dédupe via `isLoaded`, échecs silencieux, jamais bloquant.
 */
export const usePrefetch = () => {
  const nuxtApp = useNuxtApp()

  const prefetchRoute = async (
    name: unknown,
    params: Record<string, string | string[]>,
  ): Promise<void> => {
    await nuxtApp.runWithContext(async () => {
      if (useSpace() === 'public' || !useUserSession().loggedIn.value) return

      const rule = getPrefetchRegistry()[routeKey(name)]
      if (!rule) return

      // Aucune partie interrogative : l'anticipation vise une route qu'on n'a pas encore ouverte,
      // donc sans filtre choisi. Un écran filtré charge sa vue entière, puis affine à la demande.
      const loaders = rule({ params: normalizeParams(params), query: {} })
      await Promise.all(
        loaders
          .filter((loader) => !loader.isLoaded())
          .map((loader) => loader.load().catch(() => undefined)),
      )
    })
  }

  return { prefetchRoute }
}
