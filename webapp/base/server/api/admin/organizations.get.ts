import type { OrganizationReviewQueue } from '#shared/types/organization'

/**
 * File de revue admin : relais de GET /v1/admin/organizations.
 * L'API reste juge de l'accès (ROLE_ADMIN) — le BFF ne filtre rien lui-même.
 */
export default defineEventHandler(async (event) => {
  const { status, page, perPage } = getQuery(event)

  return await apiFetchForUser<OrganizationReviewQueue>(event, '/v1/admin/organizations', {
    query: { status, page, perPage },
  })
})
