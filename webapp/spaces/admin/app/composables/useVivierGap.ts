import { useVivierGapStore, vivierGapKey } from '../stores/vivierGap'

/**
 * Détail d'un manque de qualification — lecteur du store, chargé par `loadVivierGap` via le
 * registre. La métrique vient du chemin, le motif filtré de la partie interrogative : les deux
 * forment l'identité de la vue.
 *
 * Ne rend la vue que si le store porte bien CETTE métrique ET CE motif, sans quoi la liste du motif
 * précédent resterait à l'écran le temps du chargement suivant — la répartition, elle, ne bouge pas,
 * et la confusion serait invisible.
 */
export const useVivierGap = (metric: string, reason: Ref<string>) => {
  const store = useVivierGapStore()
  const { status } = useAppBootstrap()

  const gap = computed(() =>
    store.getLoadedKey === vivierGapKey(metric, reason.value) ? store.getGap : null,
  )
  const pending = computed(() => status.value === 'loading' && gap.value === null)
  const error = computed(() => status.value === 'error')

  return { gap, pending, error }
}
