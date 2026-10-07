import type { components } from '#shared/types/api'

// État partagé du snapshot vivier : deux écrans admin (santé, candidats) le lisent via une source
// unique au lieu d'un ref local chacun. Hydraté par useVivier ; le store ne touche pas le
// réseau et n'enrichit pas (le snapshot est déjà prêt à l'affichage), il porte juste l'état.
type AdminVivier = components['schemas']['AdminVivier']

type VivierState = {
  vivier: AdminVivier | null
}

export const useVivierStore = defineStore('vivier', {
  state: (): VivierState => ({
    vivier: null,
  }),

  getters: {
    getVivier(state: VivierState): AdminVivier | null {
      return state.vivier
    },
  },

  actions: {
    setVivier(payload: AdminVivier | null): void {
      this.vivier = payload && typeof payload === 'object' ? payload : null
    },

    reset(): void {
      this.vivier = null
    },
  },
})
