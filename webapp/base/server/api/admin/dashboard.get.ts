import type { AdminDashboard } from '#shared/types/dashboard'

/**
 * Centre de contrôle admin : relais de GET /v1/admin/dashboard. L'API garde ROLE_ADMIN.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<AdminDashboard>(event, '/v1/admin/dashboard')
})
