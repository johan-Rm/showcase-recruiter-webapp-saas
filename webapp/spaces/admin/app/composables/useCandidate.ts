import { useCandidateStore } from '../stores/candidate'

/**
 * Fiche d'un candidat pour l'admin — lecteur du store. Profil complet NON masqué ;
 * l'API garde ROLE_ADMIN (404 si l'uuid est inconnu → état d'erreur). L'id de la route est chargé
 * par `loadCandidate` (registre) via le middleware, avant l'affichage.
 */
export const useCandidate = (id: string) => {
  const store = useCandidateStore()
  const { status } = useAppBootstrap()

  // Ne rendre la fiche que si le store porte bien CET id — évite d'afficher brièvement un autre
  // candidat au moment de passer d'une fiche à l'autre.
  const candidate = computed(() => (store.getLoadedId === id ? store.getCandidate : null))
  const pending = computed(() => status.value === 'loading' && candidate.value === null)
  const error = computed(() => status.value === 'error')

  return { candidate, pending, error }
}
