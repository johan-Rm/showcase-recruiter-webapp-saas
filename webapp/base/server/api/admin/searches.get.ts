import type { AdminOverflowingSearchCollection } from '#shared/types/admin-search'

/**
 * Les recherches qui débordent : relais de GET /v1/admin/searches. L'API garde ROLE_ADMIN.
 *
 * Lecture seule — l'inventaire dit où le vivier ramène plus que ce qu'un écran recruteur peut
 * montrer. Pagination relayée telle quelle : c'est l'API qui trie et qui borne.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<AdminOverflowingSearchCollection>(event, '/v1/admin/searches', {
    query: getQuery(event),
  })
})
