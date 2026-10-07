import { useDashboardStore } from '../stores/dashboard'

/**
 * Compteurs de la home admin — lecteur du store : l'orchestrateur charge, la page lit.
 * File de revue, connexions, dévoilements, inscriptions (7 jours glissants) et volume du vivier.
 * Le chargement passe par `loadDashboard` (registre) ; l'API garde ROLE_ADMIN.
 */
export const useDashboard = () => {
  const store = useDashboardStore()
  const { status } = useAppBootstrap()

  const dashboard = computed(() => store.getDashboard)
  const pending = computed(() => status.value === 'loading' && dashboard.value === null)
  const error = computed(() => status.value === 'error')

  return { dashboard, pending, error }
}
