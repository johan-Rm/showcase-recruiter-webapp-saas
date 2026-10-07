import type { AdminOrganizationDetail } from '#shared/types/admin-organization'

/**
 * La fiche d'une entreprise : relais de GET /v1/admin/organizations/{id}. L'API garde ROLE_ADMIN.
 *
 * Lecture seule. `?transactions` borne le journal de crédits et se relaie tel quel.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<AdminOrganizationDetail>(event, `/v1/admin/organizations/${id}`, {
    query: getQuery(event),
  })
})
