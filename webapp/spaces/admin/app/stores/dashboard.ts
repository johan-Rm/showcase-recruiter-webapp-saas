import type { AdminDashboard } from '#shared/types/dashboard'

// État partagé des compteurs de la home admin (activité 7 jours glissants, l'API garde ROLE_ADMIN).
// Hydraté par loadDashboard ; le store ne touche pas le réseau et n'enrichit pas (les compteurs
// sont déjà prêts à l'affichage), il porte juste l'état.
type DashboardState = {
  dashboard: AdminDashboard | null
}

export const useDashboardStore = defineStore('dashboard', {
  state: (): DashboardState => ({
    dashboard: null,
  }),

  getters: {
    getDashboard(state: DashboardState): AdminDashboard | null {
      return state.dashboard
    },
  },

  actions: {
    setDashboard(payload: AdminDashboard | null): void {
      this.dashboard = payload && typeof payload === 'object' ? payload : null
    },
    reset(): void {
      this.dashboard = null
    },
  },
})
