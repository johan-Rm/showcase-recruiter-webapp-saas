/**
 * Démo du relais BFF → API : GET /v1/me avec le Bearer de la session.
 * Valide de bout en bout cookie httpOnly → JWT → autorisation API.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<Record<string, unknown>>(event, '/v1/me')
})
