import { ADMIN_BOOTSTRAP_REGISTRY } from '../bootstrap/registry'
import { ADMIN_PREFETCH_RULES } from '../bootstrap/prefetch'

// Pose les registres de l'espace admin : bootstrap (critique/preload, résolu en SSR → plugin
// universel) et préchargement prédictif (056). Un seul plugin, les deux registres.
export default defineNuxtPlugin(() => {
  setBootstrapRegistry(ADMIN_BOOTSTRAP_REGISTRY)
  setPrefetchRegistry(ADMIN_PREFETCH_RULES)
})
