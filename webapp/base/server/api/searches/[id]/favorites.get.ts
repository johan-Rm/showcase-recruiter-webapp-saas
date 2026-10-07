import type { SearchFavoriteCollection } from '#shared/types/search'

/**
 * Les profils retenus dans une recherche (GET /v1/searches/{searchId}/favorites) : relais.
 *
 * SERVI À PART du jeu de résultats, et sans pagination : `/results` sert une PAGE, et les favoris qui
 * n'y figurent pas resteraient inconnus — le compteur de l'onglet annoncerait alors ce qu'on a
 * chargé au lieu de ce qu'on a retenu.
 *
 * Lecture ouverte à qui peut lire la recherche : le destinataire d'un partage voit les favoris de
 * l'auteur, sans pouvoir y toucher.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<SearchFavoriteCollection>(event, `/v1/searches/${id}/favorites`)
})
