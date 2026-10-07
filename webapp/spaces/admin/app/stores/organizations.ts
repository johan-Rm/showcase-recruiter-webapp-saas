import { toReviewCard } from '#services/organization/review'

import type {
  OrganizationReviewCard,
  OrganizationReviewQueue,
  OrganizationReviewStatus,
} from '#shared/types/organization'

// État partagé de la file de revue des entreprises. Hydraté par loadOrganizations / le composable ;
// le store ne touche pas le réseau. State brut = la file paginée renvoyée par l'API POUR UN FILTRE
// (le store n'en tient qu'un à la fois — `loadedStatus` dit lequel). Le getter délègue
// l'enrichissement (DTO → carte prête à rendre) au service `toReviewCard`.
type OrganizationsState = {
  queue: OrganizationReviewQueue | null
  loadedStatus: OrganizationReviewStatus | null
}

export const useOrganizationsStore = defineStore('organizations', {
  state: (): OrganizationsState => ({
    queue: null,
    loadedStatus: null,
  }),

  getters: {
    getQueue(state: OrganizationsState): OrganizationReviewQueue | null {
      return state.queue
    },
    getOrganizations(state: OrganizationsState): OrganizationReviewCard[] {
      return (state.queue?.items ?? []).map(toReviewCard)
    },
    getTotal(state: OrganizationsState): number {
      return state.queue?.total ?? 0
    },
    getLoadedStatus(state: OrganizationsState): OrganizationReviewStatus | null {
      return state.loadedStatus
    },
  },

  actions: {
    setQueue(queue: OrganizationReviewQueue | null, status: OrganizationReviewStatus): void {
      this.queue = queue && typeof queue === 'object' ? queue : null
      this.loadedStatus = status
    },
    reset(): void {
      this.queue = null
      this.loadedStatus = null
    },
  },
})
