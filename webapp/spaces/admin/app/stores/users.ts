import type { AdminUser, AdminUserQueue } from '#shared/types/user'

// État partagé des comptes tous espaces confondus (rôles, statut, dernière connexion ; l'API garde
// ROLE_ADMIN). Hydraté par loadUsers ; le store ne touche pas le réseau. State brut = la file
// paginée ; les getters en exposent la lecture et l'état dérivé (liste, total).
type UsersState = {
  queue: AdminUserQueue | null
}

export const useUsersStore = defineStore('users', {
  state: (): UsersState => ({
    queue: null,
  }),

  getters: {
    getQueue(state: UsersState): AdminUserQueue | null {
      return state.queue
    },
    getUsers(state: UsersState): AdminUser[] {
      return state.queue?.items ?? []
    },
    getTotal(state: UsersState): number {
      return state.queue?.total ?? 0
    },
  },

  actions: {
    setQueue(payload: AdminUserQueue | null): void {
      this.queue = payload && typeof payload === 'object' ? payload : null
    },
    reset(): void {
      this.queue = null
    },
  },
})
