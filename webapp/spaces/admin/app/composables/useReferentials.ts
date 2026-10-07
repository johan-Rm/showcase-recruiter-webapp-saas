import { useReferentialsStore } from '../stores/referentials'

import type { components } from '#shared/types/api'

type AdminReferential = components['schemas']['AdminReferential']
type ReferentialType = 'METIER' | 'COMPETENCE'
type ReviewStatus = 'VALIDE' | 'DEPRECIE'

/**
 * Référentiels qualifiés et relecture humaine — passerelle réactive au-dessus du store
 *. Le référentiel se lit du store ; le composable porte le triptyque de filtres
 * (type / page / statut), les (re)chargements et les deux relectures (concepts, libellés mot à mot).
 *
 * Le tri par fréquence et la couverture cumulée viennent de l'API : la relecture se PRIORISE.
 */
export const useReferentials = (type: Ref<ReferentialType>) => {
  const store = useReferentialsStore()
  const request = useRequestFetch()

  const page = ref(1)
  const status = ref<string>('')
  const pending = ref(false)
  const error = ref(false)

  const referential = computed(() => store.getReferential)
  const currentKey = computed(() => `${type.value}|${page.value}|${status.value}`)

  // Recharge la page courante (type/page/statut) → remplace le contenu du store.
  const reload = async (): Promise<void> => {
    pending.value = true
    error.value = false

    try {
      const data = await request<AdminReferential>(`/api/admin/referentials/${type.value}`, {
        query: { page: page.value, perPage: 25, ...(status.value ? { status: status.value } : {}) },
      })
      store.setReferential(data, currentKey.value)
    } catch {
      error.value = true
    } finally {
      pending.value = false
    }
  }

  // Repartir en page 1 quand le périmètre change : rester en page 7 d'une autre liste n'a pas de sens.
  watch([type, status], () => {
    page.value = 1
  })

  // (Re)charger dès que le triplet n'est pas celui en store — au changement de filtre/page, et au
  // retour sur l'écran. Le préchargé est METIER page 1, donc pas de fetch au 1er affichage.
  watch(
    currentKey,
    (key) => {
      if (store.getLoadedKey !== key) reload()
    },
    { immediate: true },
  )

  const review = async (codes: string[], decision: ReviewStatus): Promise<void> => {
    if (codes.length === 0) return
    await request('/api/admin/referentials/reviews', {
      method: 'POST',
      body: { codes, status: decision },
    })
    await reload()
  }

  // Relecture « mot à mot » : déprécier = exclure de l'élargissement, CANDIDAT = restaurer.
  const reviewLabels = async (
    labelIds: string[],
    decision: 'DEPRECIE' | 'CANDIDAT',
  ): Promise<void> => {
    if (labelIds.length === 0) return
    await request('/api/admin/referentials/labels/reviews', {
      method: 'POST',
      body: { labelIds, status: decision },
    })
    await reload()
  }

  return { referential, pending, error, page, status, review, reviewLabels }
}
