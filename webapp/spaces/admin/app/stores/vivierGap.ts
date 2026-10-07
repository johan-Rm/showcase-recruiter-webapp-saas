import type { components } from '#shared/types/api'

// Détail d'un manque de qualification : qui la métrique ne compte pas, et pourquoi.
//
// Le store ne tient QU'UNE vue à la fois — `loadedKey` dit laquelle, métrique et motif filtré
// compris. Le motif vit dans l'adresse : deux vues de la même métrique sont deux états différents,
// et sans la clé complète un retour au motif précédent afficherait la liste du suivant.
type AdminVivierGap = components['schemas']['AdminVivierGap']

type VivierGapState = {
  gap: AdminVivierGap | null
  loadedKey: string | null
}

/** Identité d'une vue : ce qui la distingue de toute autre. */
export const vivierGapKey = (metric: string, reason: string): string => `${metric}|${reason}`

export const useVivierGapStore = defineStore('vivierGap', {
  state: (): VivierGapState => ({
    gap: null,
    loadedKey: null,
  }),

  getters: {
    getGap(state: VivierGapState): AdminVivierGap | null {
      return state.gap
    },
    getLoadedKey(state: VivierGapState): string | null {
      return state.loadedKey
    },
  },

  actions: {
    setGap(payload: AdminVivierGap | null, key: string): void {
      this.gap = payload && typeof payload === 'object' ? payload : null
      this.loadedKey = key
    },

    reset(): void {
      this.gap = null
      this.loadedKey = null
    },
  },
})
