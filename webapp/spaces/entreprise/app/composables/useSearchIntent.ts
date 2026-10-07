import { withIntent } from '#services/search/preload'

import { useCandidatesStore } from '../stores/candidates'
import { useSearchesStore } from '../stores/searches'

/**
 * CE QUE LE RECRUTEUR S'APPRÊTE À OUVRIR.
 *
 * Survoler une carte, y poser le focus ou la faire entrer à l'écran dit la même chose une demi-
 * seconde avant le clic : « c'est celle-là ». Le chargement de fond suit un ordre qui ignorait ce
 * signal — sur l'écran de collection, la carte qu'on vise peut être la dernière de la file.
 *
 * Ce composable ne charge RIEN. Il tient une liste de recherches désignées, que le fond consulte à
 * chaque pas : la règle du seul appel en vol reste intacte, le palier en cours finit, et la désignée
 * passe ensuite. Un appel immédiat aurait été plus réactif et aurait rendu le préchargement capable
 * de passer devant un geste du recruteur.
 *
 * `useState` et non un `ref` de module : la liste doit survivre au changement d'écran, et traverser
 * le rendu serveur comme le reste de l'état de chargement.
 */
export const useSearchIntent = () => {
  const intended = useState<string[]>('entreprise.preload.intended', () => [])
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()

  /**
   * Cette recherche a-t-elle encore quelque chose à charger ?
   *
   * Une recherche déjà complète ne réordonne rien : la désigner ferait remonter dans la file une
   * entrée que le fond saute de toute façon, et repousserait celles qui, elles, attendent.
   */
  const isPending = (searchId: string): boolean => {
    const ids = searches.getResultIds(searchId)

    return ids.length > 0 && candidates.getMissingIds(ids).length > 0
  }

  return {
    intended: readonly(intended),
    /** Désigne une recherche. Sans effet si elle est déjà chargée, ou déjà en tête. */
    intend: (searchId: string): void => {
      if (!isPending(searchId) || searchId === intended.value[0]) return

      intended.value = withIntent(intended.value, searchId)
    },
  }
}
