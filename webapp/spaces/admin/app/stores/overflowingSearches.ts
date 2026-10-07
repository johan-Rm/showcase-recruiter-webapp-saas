import type { AdminOverflowingSearchCollection } from '#shared/types/admin-search'

// L'inventaire des recherches qui débordent, tel que l'API le sert : une PAGE, et le total qui
// décrit la liste entière. Le store n'en tient qu'une — `loadedPage` dit laquelle, ce qui permet au
// composable de savoir s'il doit recharger en revenant sur l'écran.
//
// Le store ne touche pas le réseau : il est hydraté par le chargeur de bootstrap ou par le
// composable. La liste arrive déjà triée et bornée par l'API ; aucun enrichissement ici.
type OverflowingSearchesState = {
  collection: AdminOverflowingSearchCollection | null
  loadedPage: number | null
}

export const useOverflowingSearchesStore = defineStore('overflowingSearches', {
  state: (): OverflowingSearchesState => ({
    collection: null,
    loadedPage: null,
  }),

  getters: {
    getCollection(state: OverflowingSearchesState): AdminOverflowingSearchCollection | null {
      return state.collection
    },
    getLoadedPage(state: OverflowingSearchesState): number | null {
      return state.loadedPage
    },
  },

  actions: {
    setCollection(collection: AdminOverflowingSearchCollection | null, page: number): void {
      this.collection = collection && typeof collection === 'object' ? collection : null
      this.loadedPage = page
    },
    reset(): void {
      this.collection = null
      this.loadedPage = null
    },
  },
})
