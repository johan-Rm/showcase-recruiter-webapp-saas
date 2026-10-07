import { useVivierStore } from '../stores/vivier'

/**
 * Santé du vivier — lecteur du store : l'orchestrateur charge, la page lit. Le
 * chargement passe par `loadVivier` (registre de bootstrap) ; ici on n'expose que l'état lu et le
 * `pending` / `error` dérivés du statut du bootstrap. L'API garde ROLE_ADMIN.
 */
export const useVivier = () => {
  const store = useVivierStore()
  const { status } = useAppBootstrap()

  const vivier = computed(() => store.getVivier)
  const pending = computed(() => status.value === 'loading' && vivier.value === null)
  const error = computed(() => status.value === 'error')

  return { vivier, pending, error }
}
