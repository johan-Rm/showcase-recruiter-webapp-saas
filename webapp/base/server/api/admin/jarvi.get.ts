import type { AdminJarvi } from '#shared/types/jarvi'

/**
 * Supervision Jarvi : relais de GET /v1/admin/jarvi. L'API garde ROLE_ADMIN.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<AdminJarvi>(event, '/v1/admin/jarvi')
})
