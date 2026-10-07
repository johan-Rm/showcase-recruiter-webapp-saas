import type { CandidateProfile } from '#shared/types/candidate-profile'

/**
 * Un profil complet (GET /v1/candidates/{id}) : relais.
 *
 * L'API reste juge de l'accès et du masquage — les coordonnées, le CV et le compte rendu d'entretien
 * n'arrivent ici que si l'organisation du recruteur les a dévoilés. Le relais ne filtre rien : ce
 * qu'il ne reçoit pas ne descend pas, et c'est toute la garantie.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<CandidateProfile>(event, `/v1/candidates/${id}`)
})
