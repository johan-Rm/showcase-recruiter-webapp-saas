import { useCandidatesStore } from '../stores/candidates'

/**
 * Vivier cross-tenant admin — lecteur du store : l'orchestrateur charge, la page lit.
 * Liste NON masquée ; l'API garde ROLE_ADMIN. Le chargement passe par `loadCandidates` (registre) ;
 * ici on n'expose que l'état lu + le `pending` / `error` dérivés du statut du bootstrap.
 */
export const useCandidates = () => {
  const store = useCandidatesStore()
  const { status } = useAppBootstrap()

  const candidates = computed(() => store.getCandidates)
  const total = computed(() => store.getTotal)
  const pending = computed(() => status.value === 'loading' && store.getQueue === null)
  const error = computed(() => status.value === 'error')

  return { candidates, total, pending, error }
}
