import type { BootstrapLoader, BootstrapRouteData, BootstrapStatus } from '#shared/types/bootstrap'

const EMPTY_ROUTE_DATA: BootstrapRouteData = { critical: [], background: [] }

// Nom de route sans le suffixe i18n (`vivier___fr` → `vivier`), clé du registre.
const routeKey = (name: unknown): string => String(name ?? '').replace(/___[a-z]{2}$/, '')

// Paramètres de chemin et partie interrogative arrivent sous la même forme lâche — valeur seule,
// répétée, ou vide. Un résolveur lit des chaînes : une clé sans valeur vaut chaîne vide.
const normalizeRecord = (source: RouteRecord): Record<string, string> =>
  Object.fromEntries(
    Object.entries(source).map(([key, value]) => [
      key,
      (Array.isArray(value) ? value[0] : value) ?? '',
    ]),
  )

/**
 * Orchestrateur de chargement. Séquence les données en TROIS paliers : métadonnées puis
 * critique, tous deux bloquants (SSR au 1er rendu, shell en nav client), puis arrière-plan différé.
 * Il ne fetch pas lui-même — il exécute les loaders déclarés par l'espace, qui hydratent les stores.
 * Les pages lisent ensuite les stores ; le `pending`/`error` d'une vue se lit sur le statut exposé
 * ici.
 */
export const useAppBootstrap = () => {
  const nuxtApp = useNuxtApp()
  const status = useState<BootstrapStatus>('app.bootstrap.status', () => 'idle')
  const { handleSessionExpiry } = useSessionExpiry()

  const resolve = (name: unknown, params: RouteRecord, query: RouteRecord): BootstrapRouteData => {
    const resolver = getBootstrapRegistry()[routeKey(name)]
    return resolver
      ? resolver({ params: normalizeRecord(params), query: normalizeRecord(query) })
      : EMPTY_ROUTE_DATA
  }

  // Palier 1, hors route : les mêmes loaders quelle que soit la page. Résolu à chaque appel, pas
  // une fois pour toutes — un loader capture son store, il doit naître dans le contexte courant.
  const resolveMetadata = (): BootstrapLoader[] => getBootstrapMetadata()()

  const shouldBootstrap = (): boolean => {
    if (useSpace() === 'public') return false
    return useUserSession().loggedIn.value
  }

  /**
   * Exécute les paliers bloquants manquants en pilotant le statut (→ shell) : SÉQUENTIELS entre eux,
   * parallèles à l'intérieur. L'ordre porte une règle — les métadonnées sont les relations des
   * données critiques (référentiels, constantes), un critique servi avant elles s'afficherait avec
   * des libellés vides le temps qu'elles arrivent.
   *
   * No-op si tout est déjà en store : le statut passe `ready` sans jamais afficher de chargement.
   */
  const runBlocking = async (stages: BootstrapLoaderList[]): Promise<void> => {
    const pending = stages
      .map((stage) => stage.filter((loader) => !loader.isLoaded()))
      .filter((stage) => stage.length > 0)

    if (pending.length === 0) {
      status.value = 'ready'
      return
    }

    status.value = 'loading'
    try {
      for (const stage of pending) {
        await Promise.all(stage.map((loader) => loader.load()))
      }
      status.value = 'ready'
    } catch (cause) {
      // Une session close n'est pas une panne de chargement : la ranger avec les autres afficherait
      // « vos données n'ont pas pu être chargées » à quelqu'un qui n'a plus qu'à se reconnecter, sur
      // un écran qui porte encore son nom. Elle sort donc du lot ici, où le chargement d'un écran
      // passe par les paliers — au rendu serveur, la redirection part alors avant tout affichage.
      if (await handleSessionExpiry(cause)) {
        return
      }

      status.value = 'error'
    }
  }

  // Paliers 1-2, bloquant SSR : amorcé une fois via callOnce dans app.vue.
  const initCoreData = async (): Promise<void> => {
    if (!shouldBootstrap()) {
      status.value = 'ready'
      return
    }

    const route = useRoute()
    await runBlocking([resolveMetadata(), resolve(route.name, route.params, route.query).critical])
  }

  // Nav CLIENT : charge le critique de la route cible si absent du store (shell pendant ce temps).
  // Les métadonnées repassent en tête — no-op au cas normal (déjà chargées au boot), filet si la
  // session s'est ouverte après le premier rendu.
  const loadRouteCritical = async (
    name: unknown,
    params: RouteRecord,
    query: RouteRecord,
  ): Promise<void> => {
    await nuxtApp.runWithContext(async () => {
      if (!shouldBootstrap()) return
      await runBlocking([resolveMetadata(), resolve(name, params, query).critical])
    })
  }

  // Palier 3 : arrière-plan, échecs silencieux, jamais bloquant. Déclenché à runtimeReady.
  const loadBackgroundData = async (
    name: unknown,
    params: RouteRecord,
    query: RouteRecord,
  ): Promise<void> => {
    await nuxtApp.runWithContext(async () => {
      if (!shouldBootstrap()) return
      const { background } = resolve(name, params, query)
      await Promise.all(
        background
          .filter((loader) => !loader.isLoaded())
          .map((loader) => loader.load().catch(() => undefined)),
      )
    })
  }

  // Anticipation : précharge TOUS les domaines du registre dans les stores, au repos
  // (déclenché à runtimeReady). Dédupe par clé — un loader partagé (vivier) n'est chargé qu'une
  // fois — et laisse de côté les routes déjà en store. Échecs silencieux, jamais bloquant.
  const preloadAll = async (): Promise<void> => {
    await nuxtApp.runWithContext(async () => {
      if (!shouldBootstrap()) return

      const byKey = new Map<string, BootstrapLoader>()
      for (const resolver of Object.values(getBootstrapRegistry())) {
        for (const loader of resolver({ params: {}, query: {} }).critical) {
          // `eager: false` = domaine lourd exclu du preload au repos ; il reste critique à la nav.
          if (false === loader.eager) continue
          if (!byKey.has(loader.key)) byKey.set(loader.key, loader)
        }
      }

      await Promise.all(
        [...byKey.values()]
          .filter((loader) => !loader.isLoaded())
          .map((loader) => loader.load().catch(() => undefined)),
      )
    })
  }

  return {
    status: readonly(status),
    initCoreData,
    loadRouteCritical,
    loadBackgroundData,
    preloadAll,
  }
}

type BootstrapLoaderList = BootstrapRouteData['critical']

/** Forme brute d'un `route.params` / `route.query` avant normalisation. */
type RouteRecord = Record<string, string | null | (string | null)[]>
