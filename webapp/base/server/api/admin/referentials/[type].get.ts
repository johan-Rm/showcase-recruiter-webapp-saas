import type { components } from '#shared/types/api'

type AdminReferential = components['schemas']['AdminReferential']

/**
 * Référentiels qualifiés : relais de GET /v1/admin/referentials/{type}.
 * L'API garde ROLE_ADMIN — le référentiel est commun à toutes les entreprises.
 *
 * La pagination et le filtre de statut sont relayés tels quels : la vérité de tri
 * (fréquence décroissante, couverture cumulée) appartient à l'API.
 */
export default defineEventHandler(async (event) => {
  const type = getRouterParam(event, 'type')
  const query = getQuery(event)
  const search = new URLSearchParams(
    Object.entries(query).map(([k, v]) => [k, String(v)]),
  ).toString()

  return await apiFetchForUser<AdminReferential>(
    event,
    `/v1/admin/referentials/${type}${search ? `?${search}` : ''}`,
  )
})
