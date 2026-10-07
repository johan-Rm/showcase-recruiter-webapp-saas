import type { components } from '#shared/types/api'

type AdminVivier = components['schemas']['AdminVivier']

/**
 * Santé du vivier : relais de GET /v1/admin/vivier. L'API garde ROLE_ADMIN.
 * Lecture seule — état de la base qualifiée, mêmes chiffres que app:qualification:report.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<AdminVivier>(event, '/v1/admin/vivier')
})
