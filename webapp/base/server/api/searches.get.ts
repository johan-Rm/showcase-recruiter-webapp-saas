import type { SearchCollection } from '#shared/types/search'

/**
 * Mes recherches (GET /v1/searches) : relais. L'API décide du périmètre — les miennes, et celles
 * que l'équipe a explicitement partagées ; elle pose aussi `isOwn`, dont dépend la lecture seule.
 * Collection non paginée côté API : aucun paramètre à relayer.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<SearchCollection>(event, '/v1/searches')
})
