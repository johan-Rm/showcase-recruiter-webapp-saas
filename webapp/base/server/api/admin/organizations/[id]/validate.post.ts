import type { Organization } from '#shared/types/organization'

/** Validation d'une entreprise par un admin : relais de POST /v1/organizations/{id}/validate. */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<Organization>(event, `/v1/organizations/${id}/validate`, {
    method: 'POST',
  })
})
