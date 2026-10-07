import type { AdminCandidateDetail } from '#shared/types/candidate'

// État partagé de la fiche candidat consultée (profil complet NON masqué ; l'API garde ROLE_ADMIN).
// Le store ne tient QU'UNE fiche à la fois — `loadedId` dit laquelle. Hydraté par loadCandidate ;
// route paramétrée → non préchargeable au repos (id inconnu d'avance), chargée à la navigation.
type CandidateState = {
  candidate: AdminCandidateDetail | null
  loadedId: string | null
}

export const useCandidateStore = defineStore('candidate', {
  state: (): CandidateState => ({
    candidate: null,
    loadedId: null,
  }),

  getters: {
    getCandidate(state: CandidateState): AdminCandidateDetail | null {
      return state.candidate
    },
    getLoadedId(state: CandidateState): string | null {
      return state.loadedId
    },
  },

  actions: {
    setCandidate(candidate: AdminCandidateDetail | null, id: string): void {
      this.candidate = candidate && typeof candidate === 'object' ? candidate : null
      this.loadedId = id
    },
    reset(): void {
      this.candidate = null
      this.loadedId = null
    },
  },
})
