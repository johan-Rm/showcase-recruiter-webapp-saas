import type { AdminOrganizationDetail } from '#shared/types/admin-organization'

// État partagé de la fiche entreprise consultée (solde, abonnement, membres, journal de crédits ;
// l'API garde ROLE_ADMIN). Le store ne tient QU'UNE fiche à la fois — `loadedId` dit laquelle.
// Hydraté par loadOrganization ; route paramétrée → non préchargeable au repos (id inconnu
// d'avance), chargée à la navigation.
type OrganizationState = {
  organization: AdminOrganizationDetail | null
  loadedId: string | null
}

export const useOrganizationStore = defineStore('organization', {
  state: (): OrganizationState => ({
    organization: null,
    loadedId: null,
  }),

  getters: {
    getOrganization(state: OrganizationState): AdminOrganizationDetail | null {
      return state.organization
    },
    getLoadedId(state: OrganizationState): string | null {
      return state.loadedId
    },
  },

  actions: {
    setOrganization(organization: AdminOrganizationDetail | null, id: string): void {
      this.organization = organization && typeof organization === 'object' ? organization : null
      this.loadedId = id
    },
    reset(): void {
      this.organization = null
      this.loadedId = null
    },
  },
})
