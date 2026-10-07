import type { BootstrapMetadataResolver, BootstrapRegistry } from '#shared/types/bootstrap'

// Ce que l'espace courant déclare au bootstrap : ses métadonnées (palier 1, hors route) et sa carte
// route → données (paliers 2 et 3). Module-level ASSUMÉ SSR-safe : une build = un espace
// (SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE), donc ces valeurs sont une CONFIG STATIQUE identique à chaque requête — jamais de
// l'état de requête. Le plugin de l'espace les pose au démarrage (serveur ET client).
let currentRegistry: BootstrapRegistry = {}
let currentMetadata: BootstrapMetadataResolver = () => []

export const setBootstrapRegistry = (registry: BootstrapRegistry): void => {
  currentRegistry = registry
}

export const getBootstrapRegistry = (): BootstrapRegistry => currentRegistry

// Un espace sans métadonnées ne pose rien : le repli vide garde le palier 1 optionnel.
export const setBootstrapMetadata = (resolver: BootstrapMetadataResolver): void => {
  currentMetadata = resolver
}

export const getBootstrapMetadata = (): BootstrapMetadataResolver => currentMetadata
