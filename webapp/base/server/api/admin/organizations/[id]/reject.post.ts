import type { Organization } from '#shared/types/organization'

/** Refus d'une entreprise par un admin : relais de POST /v1/organizations/{id}/reject. */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<Organization>(event, `/v1/organizations/${id}/reject`, {
    method: 'POST',
  })
})
