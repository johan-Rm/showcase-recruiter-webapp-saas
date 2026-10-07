import type { Organization } from '#shared/types/organization'

/** La fiche de l'organisation connectée : relais. L'organisation vient de la session. */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)

  return await apiFetchForUser<Organization>(event, `/v1/organizations/${organizationId}`)
})
