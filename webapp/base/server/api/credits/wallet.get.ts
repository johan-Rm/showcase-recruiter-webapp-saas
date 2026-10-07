import type { CreditWallet } from '#shared/types/reveal'

/**
 * Le solde de crédits de l'organisation connectée : relais.
 *
 * Lu à l'ouverture d'un écran qui dépense, et après chaque dépense — c'est le seul chiffre qui dit
 * si le geste suivant est possible. L'organisation vient de la session, jamais du client.
 */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)

  return await apiFetchForUser<CreditWallet>(
    event,
    `/v1/organizations/${organizationId}/credit-wallet`,
  )
})
