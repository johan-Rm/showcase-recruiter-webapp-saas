import { useJarviStore } from '../stores/jarvi'

/**
 * Supervision de l'ingestion Jarvi — lecteur du store : l'orchestrateur charge, la page
 * lit. État des flux + agrégats du vivier ; l'API garde ROLE_ADMIN. Le chargement passe par
 * `loadJarvi` (registre) ; ici on n'expose que l'état lu + le `pending` / `error` dérivés du statut.
 */
export const useJarvi = () => {
  const store = useJarviStore()
  const { status } = useAppBootstrap()

  const jarvi = computed(() => store.getJarvi)
  const pending = computed(() => status.value === 'loading' && jarvi.value === null)
  const error = computed(() => status.value === 'error')

  return { jarvi, pending, error }
}
