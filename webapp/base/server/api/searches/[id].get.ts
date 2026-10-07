import type { Search } from '#shared/types/search'

/**
 * Une recherche (GET /v1/searches/{id}) : relais. L'API reste juge de l'accès — son auteur, ou toute
 * l'équipe si elle est partagée — et du 404.
 *
 * Ce GET n'est pas qu'une lecture : côté API, ouvrir une recherche remet à jour le repère de lecture
 * du recruteur, donc remet `newCount` à zéro pour lui seul.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<Search>(event, `/v1/searches/${id}`)
})
