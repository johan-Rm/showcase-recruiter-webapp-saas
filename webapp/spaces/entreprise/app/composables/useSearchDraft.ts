import type { SearchCriterion } from '#shared/types/search'

/**
 * LA RECHERCHE QU'ON EST EN TRAIN D'ÉCRIRE, avant qu'elle existe.
 *
 * L'écran de création se partage en deux : la carte de critères vit dans la page, le nom et le
 * lancement vivent dans la barre haute — donc dans la coque, qui n'est pas la page. Il fallait un
 * endroit commun aux deux, et c'est ici.
 *
 * `useState` et non un `ref` de module : un état de module est partagé entre deux requêtes serveur,
 * ce qui ferait fuiter le brouillon d'un recruteur dans l'écran d'un autre.
 *
 * Rien n'est mémorisé au-delà de la visite : recharger l'écran repart d'une ardoise nette. Aucune
 * recherche n'est créée côté serveur tant qu'on n'a pas lancé, il n'y a donc rien à retrouver.
 */
export const useSearchDraft = () => {
  /** Ce que la carte de critères a remonté à la dernière frappe. */
  const criteria = useState<SearchCriterion[]>('search-draft-criteria', () => [])

  /** Le nom composé depuis les critères — la page le tient à jour, elle seule sait les lire. */
  const composedName = useState('search-draft-composed-name', () => '')

  /** Le nom SAISI À LA MAIN, vide tant que l'auto-nommage tient. */
  const manualName = useState('search-draft-manual-name', () => '')

  const name = computed(() => manualName.value || composedName.value)
  const isAuto = computed(() => '' === manualName.value.trim())

  /** Repartir d'une ardoise nette : le volume d'un remplissage précédent ne survit pas à la sortie. */
  const reset = () => {
    criteria.value = []
    composedName.value = ''
    manualName.value = ''
  }

  return { criteria, composedName, manualName, name, isAuto, reset }
}
