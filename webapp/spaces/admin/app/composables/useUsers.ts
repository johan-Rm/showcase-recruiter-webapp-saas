import { useUsersStore } from '../stores/users'

/**
 * Comptes tous espaces confondus — lecteur du store : l'orchestrateur charge, la page
 * lit. Rôles, statut, dernière connexion ; l'API garde ROLE_ADMIN. Le chargement passe par
 * `loadUsers` (registre) ; ici on n'expose que l'état lu + le `pending` / `error` dérivés du statut.
 */
export const useUsers = () => {
  const store = useUsersStore()
  const { status } = useAppBootstrap()

  const users = computed(() => store.getUsers)
  const total = computed(() => store.getTotal)
  const pending = computed(() => status.value === 'loading' && store.getQueue === null)
  const error = computed(() => status.value === 'error')

  return { users, total, pending, error }
}
