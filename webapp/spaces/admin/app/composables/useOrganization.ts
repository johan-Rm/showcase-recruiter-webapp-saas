import {
  organizationScreenState,
  subscriptionOf,
  toCreditMovementRow,
} from '#services/admin/organization'

import { useOrganizationStore } from '../stores/organization'

import type { CreditMovementRow } from '#services/admin/organization'

/**
 * Fiche d'une entreprise pour l'admin — lecteur du store.
 *
 * L'id de la route est chargé par `loadOrganization` (registre) via le middleware, avant
 * l'affichage. Un identifiant inconnu se distingue d'une panne : l'API rend 404, et proposer de
 * réessayer sur une entreprise qui n'existe pas ferait tourner en rond.
 */
export const useOrganization = (id: string) => {
  const store = useOrganizationStore()
  const { status } = useAppBootstrap()

  // Ne rendre la fiche que si le store porte bien CET id — évite d'afficher brièvement une autre
  // entreprise au moment de passer d'une fiche à l'autre.
  const organization = computed(() => (store.getLoadedId === id ? store.getOrganization : null))

  // INTROUVABLE ≠ PANNE, et ça ne se lit pas dans le statut du bootstrap : le chargeur AVALE le 404
  // pour mémoriser « fiche vide pour cet id ». Le chargement se termine donc normalement, et c'est
  // le couple (id chargé, fiche nulle) qui dit l'absence.
  const notFound = computed(() => store.getLoadedId === id && null === store.getOrganization)

  const state = computed(() =>
    organizationScreenState({
      pending: 'loading' === status.value && null === organization.value && !notFound.value,
      notFound: notFound.value,
      error: 'error' === status.value,
      loaded: null !== organization.value,
    }),
  )

  const subscription = computed(() =>
    organization.value ? subscriptionOf(organization.value) : null,
  )

  const movements = computed<CreditMovementRow[]>(() =>
    (organization.value?.creditTransactions ?? []).map(toCreditMovementRow),
  )

  return { organization, subscription, movements, state }
}
