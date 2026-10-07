import type { AdminCandidateDetail } from '#shared/types/candidate'

/**
 * Fiche candidat admin : relais de GET /v1/admin/candidates/{id}.
 * L'API reste juge de l'accès (ROLE_ADMIN) et du 404.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<AdminCandidateDetail>(event, `/v1/admin/candidates/${id}`)
})
