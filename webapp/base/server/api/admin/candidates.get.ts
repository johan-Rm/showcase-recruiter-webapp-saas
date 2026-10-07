import type { AdminCandidateQueue } from '#shared/types/candidate'

/**
 * Vivier admin : relais de GET /v1/admin/candidates. L'API reste juge de l'accès
 * (ROLE_ADMIN) — le BFF ne filtre rien lui-même.
 */
export default defineEventHandler(async (event) => {
  const { page, perPage } = getQuery(event)

  return await apiFetchForUser<AdminCandidateQueue>(event, '/v1/admin/candidates', {
    query: { page, perPage },
  })
})
