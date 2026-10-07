import type { CreditTransactionCollection } from '#shared/types/reveal'

/**
 * Le journal des mouvements de crédits : relais.
 *
 * Non paginé — l'API n'expose aucun paramètre et son provider rend tout. Sur un compte ancien la
 * liste sera longue ; à revoir quand le volume le demandera.
 */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)

  return await apiFetchForUser<CreditTransactionCollection>(
    event,
    `/v1/organizations/${organizationId}/credit-transactions`,
  )
})
