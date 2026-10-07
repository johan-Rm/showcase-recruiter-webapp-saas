import type { components } from '#shared/types/api'

// État partagé du référentiel qualifié affiché. Hydraté par loadReferentials / le composable ; le
// store ne touche pas le réseau. State brut = la page renvoyée par l'API POUR UN TRIPLET
// type|page|statut (le store n'en tient qu'un — `loadedKey` dit lequel). Le référentiel est déjà
// prêt à l'affichage → getter passe-plat, pas d'enrichissement.
type AdminReferential = components['schemas']['AdminReferential']

type ReferentialsState = {
  referential: AdminReferential | null
  loadedKey: string | null
}

export const useReferentialsStore = defineStore('referentials', {
  state: (): ReferentialsState => ({
    referential: null,
    loadedKey: null,
  }),

  getters: {
    getReferential(state: ReferentialsState): AdminReferential | null {
      return state.referential
    },
    getLoadedKey(state: ReferentialsState): string | null {
      return state.loadedKey
    },
  },

  actions: {
    setReferential(referential: AdminReferential | null, key: string): void {
      this.referential = referential && typeof referential === 'object' ? referential : null
      this.loadedKey = key
    },
    reset(): void {
      this.referential = null
      this.loadedKey = null
    },
  },
})
