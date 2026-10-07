import { activityScreenState, pageCount, toActivityRow } from '#services/admin/activity'

import { ACTIVITY_PER_PAGE } from '../bootstrap/loaders'
import { useActivityStore } from '../stores/activity'

import type { ActivityRow } from '#services/admin/activity'
import type { AdminActivityCollection } from '#shared/types/admin-activity'

/**
 * Le fil des visites de profil — passerelle réactive au-dessus du store.
 *
 * Le fil se lit du store, que le bootstrap a hydraté pour la première page ; le composable porte la
 * page courante, ses rechargements et ses états. La mise en forme d'une ligne appartient au
 * service : ici on ne fait que lui passer le libellé de repli.
 *
 * Aucun tri ni filtre côté écran : l'API classe du plus récent au plus ancien, et refaire ce
 * classement ici donnerait deux ordres à tenir d'accord.
 */
export const useActivity = () => {
  const store = useActivityStore()
  const request = useRequestFetch()
  const { t } = useLocale()

  const page = ref(1)
  const pending = ref(false)
  const error = ref(false)

  const collection = computed(() => store.getCollection)

  const rows = computed<ActivityRow[]>(() =>
    (collection.value?.items ?? []).map((entry) =>
      toActivityRow(entry, t('admin.activity.unknown')),
    ),
  )

  const total = computed(() => collection.value?.total ?? 0)
  const totalPages = computed(() => pageCount(total.value, ACTIVITY_PER_PAGE))

  /**
   * CE QUE L'ÉCRAN MONTRE — un seul état, décidé par le service.
   *
   * Trois booléens lus dans le gabarit laisseraient l'ordre de priorité s'écrire en `v-else-if`, où
   * rien ne le vérifie : une panne pourrait s'y afficher comme « personne n'a consulté de profil ».
   */
  const state = computed(() =>
    activityScreenState({ pending: pending.value, error: error.value, total: total.value }),
  )

  const reload = async (): Promise<void> => {
    pending.value = true
    error.value = false

    try {
      store.setCollection(
        await request<AdminActivityCollection>('/api/admin/activity', {
          query: { page: page.value, perPage: ACTIVITY_PER_PAGE },
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
