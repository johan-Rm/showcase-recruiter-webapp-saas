import type { PrefetchRegistry } from '#shared/types/bootstrap'

// Règles de préchargement prédictif de l'espace courant. Module-level, SSR-safe pour la
// même raison que `bootstrapRegistry` : config STATIQUE identique à chaque requête (une build = un
// espace). Posé par le plugin de l'espace.
let currentRules: PrefetchRegistry = {}

export const setPrefetchRegistry = (rules: PrefetchRegistry): void => {
  currentRules = rules
}

export const getPrefetchRegistry = (): PrefetchRegistry => currentRules
