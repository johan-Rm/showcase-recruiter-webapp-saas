import type { AdminActivityCollection } from '#shared/types/admin-activity'

/**
 * Le fil des visites de profil : relais de GET /v1/admin/activity. L'API garde ROLE_ADMIN.
 *
 * Lecture seule. Pagination relayée telle quelle : c'est l'API qui trie, du plus récent au plus
 * ancien, et qui borne.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<AdminActivityCollection>(event, '/v1/admin/activity', {
    query: getQuery(event),
  })
})
