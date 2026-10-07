/**
 * Jalons de cadencement du chargement différé, pilotés par le plugin `deferred-runtime.client`.
 *
 * Lecture seule : ce composable n'ordonnance rien, il expose l'état pour que le non-critique
 * (palier 3 du bootstrap, préchargement N+1, flux SSE) ne démarre jamais avant le rendu utile.
 * L'exposer en `readonly` empêche un consommateur UI de désynchroniser les jalons du plugin.
 *
 * Ordre des paliers : `pageFinished` (navigation Nuxt visible terminée) → `runtimeReady`
 * (post-rendu contrôlé) → `passiveReady` (1re interaction utilisateur ou fallback temporel).
 */
export const useDeferredRuntime = () => {
  const pageFinished = useState<boolean>('runtime.page-finished', () => false)
  const runtimeReady = useState<boolean>('deferred.runtime.ready', () => false)
  const passiveReady = useState<boolean>('deferred.passive.ready', () => false)

  return {
    pageFinished: readonly(pageFinished),
    runtimeReady: readonly(runtimeReady),
    passiveReady: readonly(passiveReady),
  }
}
