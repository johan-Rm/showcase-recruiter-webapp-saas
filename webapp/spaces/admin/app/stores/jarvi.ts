import type { AdminJarvi } from '#shared/types/jarvi'

// État partagé de la supervision Jarvi (état des flux d'ingestion + agrégats ; l'API garde
// ROLE_ADMIN). Hydraté par loadJarvi ; le store ne touche pas le réseau et n'enrichit pas
// (déjà prêt à l'affichage), il porte juste l'état.
type JarviState = {
  jarvi: AdminJarvi | null
}

export const useJarviStore = defineStore('jarvi', {
  state: (): JarviState => ({
    jarvi: null,
  }),

  getters: {
    getJarvi(state: JarviState): AdminJarvi | null {
      return state.jarvi
    },
  },

  actions: {
    setJarvi(payload: AdminJarvi | null): void {
      this.jarvi = payload && typeof payload === 'object' ? payload : null
    },
    reset(): void {
      this.jarvi = null
    },
  },
})
