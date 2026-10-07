import { overflowScreenState, pageCount, toOverflowRow } from '#services/search/overflow'

import { OVERFLOW_PER_PAGE } from '../bootstrap/loaders'
import { useOverflowingSearchesStore } from '../stores/overflowingSearches'

import type { OverflowRow } from '#services/search/overflow'
import type { AdminOverflowingSearchCollection } from '#shared/types/admin-search'

/**
 * Les recherches qui débordent — passerelle réactive au-dessus du store.
 *
 * L'inventaire se lit du store, que le bootstrap a hydraté pour la première page ; le composable
 * porte la page courante, ses rechargements et ses états. La mise en forme d'une ligne appartient
 * au service : ici on ne fait que lui passer la traduction.
 *
 * Aucun tri ni filtre côté écran : l'API classe du plus large au plus étroit, et refaire ce classement
 * ici donnerait deux ordres à tenir d'accord.
 */
export const useOverflowingSearches = () => {
  const store = useOverflowingSearchesStore()
  const request = useRequestFetch()
  const { t } = useLocale()

  const page = ref(1)
  const pending = ref(false)
  const error = ref(false)

  const collection = computed(() => store.getCollection)

  const rows = computed<OverflowRow[]>(() =>
    (collection.value?.items ?? []).map((search) => toOverflowRow(search, t)),
  )

  const total = computed(() => collection.value?.total ?? 0)
  const totalPages = computed(() => pageCount(total.value, OVERFLOW_PER_PAGE))

  /**
   * CE QUE L'ÉCRAN MONTRE — un seul état, décidé par le service.
   *
   * Trois booléens lus dans le gabarit laissaient l'ordre de priorité s'écrire en `v-else-if`, où
   * rien ne le vérifie : une panne pouvait s'y afficher comme « aucun débordement ».
   */
  const state = computed(() =>
    overflowScreenState({ pending: pending.value, error: error.value, total: total.value }),
  )

  const reload = async (): Promise<void> => {
    pending.value = true
    error.value = false

    try {
      store.setCollection(
        await request<AdminOverflowingSearchCollection>('/api/admin/searches', {
          query: { page: page.value, perPage: OVERFLOW_PER_PAGE },
        }),
        page.value,
      )
    } catch {
      error.value = true
    } finally {
      pending.value = false
    }
  }

  // Charger dès que la page demandée n'est pas celle du store — au changement de page, et au retour
  // sur l'écran après une visite ailleurs. La première est déjà là, préchargée : rien ne part.
  watch(
    page,
    (current) => {
      if (store.getLoadedPage !== current) void reload()
    },
    { immediate: true },
  )

  return { rows, total, page, totalPages, state }
}
