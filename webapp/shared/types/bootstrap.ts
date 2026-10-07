/**
 * Contrats du bootstrap de données. Un loader sait dire s'il est déjà chargé et
 * sait charger ; le registre associe une route à ses loaders critiques (bloquants) et d'arrière-plan.
 */

export interface BootstrapLoader {
  /** Clé stable (l'endpoint) : dédupe un loader partagé par plusieurs routes lors d'un preload global. */
  key: string
  /** La donnée est-elle déjà en store ? Un loader déjà chargé ne rebloque pas et ne refetch pas. */
  isLoaded: () => boolean
  /** Charge la donnée et hydrate son store. La déduplication est portée par `isLoaded`. */
  load: () => Promise<void>
  /**
   * Précharger ce loader dans l'anticipation au repos (`preloadAll`) ? Défaut : oui.
   * À passer à `false` pour un domaine LOURD et rarement la page d'atterrissage : il reste
   * critique à la navigation, mais ne pèse plus sur CHAQUE chargement (ni n'étrangle les autres).
   */
  eager?: boolean
}

export interface BootstrapRouteData {
  /** Bloquant : retient le rendu (SSR) ou le shell (nav client) jusqu'à résolution. */
  critical: BootstrapLoader[]
  /** Différé : chargé en arrière-plan après le rendu utile (palier 3). */
  background: BootstrapLoader[]
}

export interface BootstrapContext {
  params: Record<string, string>
  /**
   * Partie interrogative de l'URL. Un écran dont le filtre y vit doit recharger quand elle change :
   * sans elle, `?motif=…` ne serait qu'une décoration d'adresse, et un rechargement de page
   * ramènerait la liste entière.
   */
  query: Record<string, string>
}

export type BootstrapRouteResolver = (ctx: BootstrapContext) => BootstrapRouteData

/** Nom de route (sans suffixe i18n) → ses données à charger. Fourni par chaque espace. */
export type BootstrapRegistry = Record<string, BootstrapRouteResolver>

/**
 * Palier 1 : les loaders INDÉPENDANTS de la route — référentiels, constantes, tout ce dont les
 * données critiques ont besoin pour être lisibles. Hors du registre de routes, car les recopier
 * dans chaque entrée ferait d'une donnée d'application une donnée d'écran.
 *
 * Une fonction et non un tableau : les loaders naissent dans un contexte Nuxt (ils appellent leur
 * store et `useRequestFetch`), un tableau posé au module-level s'évaluerait trop tôt.
 */
export type BootstrapMetadataResolver = () => BootstrapLoader[]

export type BootstrapStatus = 'idle' | 'loading' | 'ready' | 'error'

/** Règle de préchargement prédictif (056) : les loaders à anticiper quand on est sur une route. */
export type PrefetchRule = (ctx: BootstrapContext) => BootstrapLoader[]

/** Nom de route (sans suffixe i18n) → ses loaders à précharger. Fourni par chaque espace. */
export type PrefetchRegistry = Record<string, PrefetchRule>
