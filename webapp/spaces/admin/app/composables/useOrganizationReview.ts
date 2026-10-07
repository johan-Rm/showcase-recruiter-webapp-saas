import { useOrganizationsStore } from '../stores/organizations'

import type { OrganizationReviewQueue, OrganizationReviewStatus } from '#shared/types/organization'

/**
 * File de revue des entreprises — passerelle réactive au-dessus du store. La liste
 * enrichie et le total se lisent du store ; le composable porte le FILTRE (état d'UI), les
 * (re)chargements filtrés et les deux décisions admin. Rien n'est déclenché à l'invocation hors le
 * rattrapage de filtre ci-dessous.
 *
 * `EN_REVUE` par défaut : la file de travail réelle. Le store ne tient qu'un filtre à la fois.
 */
export const useOrganizationReview = () => {
  const store = useOrganizationsStore()
  const request = useRequestFetch()

  const status = ref<OrganizationReviewStatus>('EN_REVUE')
  const pending = ref(false)
  const error = ref<string | null>(null)

  const organizations = computed(() => store.getOrganizations)
  const total = computed(() => store.getTotal)

  // Recharge la file pour le filtre courant → remplace le contenu du store.
  const reload = async (): Promise<void> => {
    const queue = await request<OrganizationReviewQueue>('/api/admin/organizations', {
      query: { status: status.value },
    })
    store.setQueue(queue, status.value)
  }

  // Rattrapage de filtre : (re)charger dès que le filtre courant n'est pas celui déjà en store —
  // au changement de filtre, ET au retour sur l'écran après en avoir changé (le préchargé est
  // `EN_REVUE`, donc pas de fetch au 1er affichage).
  watch(
    status,
    (value) => {
      if (store.getLoadedStatus !== value) reload()
    },
    { immediate: true },
  )

  const decide = async (id: string, decision: 'validate' | 'reject'): Promise<void> => {
    pending.value = true
    error.value = null

    try {
      await request(`/api/admin/organizations/${id}/${decision}`, { method: 'POST' })
      // La décision fait sortir l'entreprise de la file courante : on relit plutôt que de retirer la
      // ligne à la main, pour rester d'accord avec l'API (statut, compteur, pagination).
      await reload()
    } catch {
      error.value = 'admin.organizations.decisionFailed'
    } finally {
      pending.value = false
    }
  }

  return { status, organizations, total, pending, error, decide }
}
