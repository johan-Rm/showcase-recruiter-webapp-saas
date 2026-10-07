import type { AdminActivityCollection } from '#shared/types/admin-activity'

// Le fil des visites, tel que l'API le sert : une PAGE, et le total qui décrit le fil entier. Le
// store n'en tient qu'une — `loadedPage` dit laquelle, ce qui permet au composable de savoir s'il
// doit recharger en revenant sur l'écran.
//
// Le store ne touche pas le réseau : il est hydraté par le chargeur de bootstrap ou par le
// composable. Le fil arrive déjà trié et borné par l'API ; aucun enrichissement ici.
type ActivityState = {
  collection: AdminActivityCollection | null
  loadedPage: number | null
}

export const useActivityStore = defineStore('activity', {
  state: (): ActivityState => ({
    collection: null,
    loadedPage: null,
  }),

  getters: {
    getCollection(state: ActivityState): AdminActivityCollection | null {
      return state.collection
    },
    getLoadedPage(state: ActivityState): number | null {
      return state.loadedPage
    },
  },

  actions: {
    setCollection(collection: AdminActivityCollection | null, page: number): void {
      this.collection = collection && typeof collection === 'object' ? collection : null
      this.loadedPage = page
    },
    reset(): void {
      this.collection = null
      this.loadedPage = null
    },
  },
})
