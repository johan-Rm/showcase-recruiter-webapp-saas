import type { AdminCandidate, AdminCandidateQueue } from '#shared/types/candidate'

// État partagé du vivier cross-tenant admin (liste NON masquée, l'API garde ROLE_ADMIN). Hydraté
// par loadCandidates ; le store ne touche pas le réseau. State brut = la file paginée renvoyée par
// l'API ; les getters en exposent la lecture et l'état dérivé (liste, total).
type CandidatesState = {
  queue: AdminCandidateQueue | null
}

export const useCandidatesStore = defineStore('candidates', {
  state: (): CandidatesState => ({
    queue: null,
  }),

  getters: {
    getQueue(state: CandidatesState): AdminCandidateQueue | null {
      return state.queue
    },
    getCandidates(state: CandidatesState): AdminCandidate[] {
      return state.queue?.items ?? []
    },
    getTotal(state: CandidatesState): number {
      return state.queue?.total ?? 0
    },
  },

  actions: {
    setQueue(payload: AdminCandidateQueue | null): void {
      this.queue = payload && typeof payload === 'object' ? payload : null
    },
    reset(): void {
      this.queue = null
    },
  },
})
