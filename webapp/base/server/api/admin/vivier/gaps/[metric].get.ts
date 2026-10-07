import type { components } from '#shared/types/api'

type AdminVivierGap = components['schemas']['AdminVivierGap']

/**
 * Relais de GET /v1/admin/vivier/gaps/{metric} : les profils qu'une qualification ne compte pas, et
 * pourquoi. L'API garde ROLE_ADMIN et juge la métrique comme le motif — le BFF ne filtre rien et ne
 * valide rien, sous peine d'avoir deux vérités sur ce qui existe.
 */
export default defineEventHandler(async (event) => {
  const metric = getRouterParam(event, 'metric')
  const { reason, page, perPage } = getQuery(event)

  return await apiFetchForUser<AdminVivierGap>(event, `/v1/admin/vivier/gaps/${metric}`, {
    query: { reason, page, perPage },
  })
})
