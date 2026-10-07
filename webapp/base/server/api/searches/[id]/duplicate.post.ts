import type { Search } from '#shared/types/search'

/**
 * Dupliquer une recherche (POST /v1/searches/{id}/duplicate) : relais.
 *
 * Ouvert à qui peut LIRE la recherche, et non à son seul auteur : c'est la porte de sortie du
 * destinataire d'une recherche partagée, qui ne peut pas l'éditer mais peut s'en faire une copie.
 *
 * La copie ne reprend que le nom et les critères — ni profils retenus, ni partage.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<Search>(event, `/v1/searches/${id}/duplicate`, { method: 'POST' })
})
