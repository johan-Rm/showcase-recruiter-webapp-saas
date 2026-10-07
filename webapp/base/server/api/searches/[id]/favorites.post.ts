import type { components } from '#shared/types/api'

type Favorite = components['schemas']['Favorite']

/**
 * Retenir un profil dans une recherche (POST /v1/searches/{searchId}/favorites) : relais.
 *
 * Le FILET LARGE : un profil masqué est un favori parfaitement valide — on retient avant de payer.
 * Ajout idempotent côté API, et réservé à l'auteur de la recherche.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const body = await readBody<{ candidateId?: string }>(event)

  return await apiFetchForUser<Favorite>(event, `/v1/searches/${id}/favorites`, {
    method: 'POST',
    body: { candidateId: body?.candidateId ?? '' },
  })
})
