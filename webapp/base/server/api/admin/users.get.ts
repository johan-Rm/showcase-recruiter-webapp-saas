import type { AdminUserQueue } from '#shared/types/user'

/**
 * Comptes & droits admin : relais de GET /v1/admin/users. L'API garde ROLE_ADMIN.
 */
export default defineEventHandler(async (event) => {
  const { page, perPage } = getQuery(event)

  return await apiFetchForUser<AdminUserQueue>(event, '/v1/admin/users', {
    query: { page, perPage },
  })
})
