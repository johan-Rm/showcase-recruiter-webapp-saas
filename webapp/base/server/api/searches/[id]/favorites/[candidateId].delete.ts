/**
 * Ne plus retenir un profil (DELETE /v1/searches/{searchId}/favorites/{candidateId}) : relais.
 *
 * Réservé à l'auteur de la recherche, comme l'ajout — une recherche partagée est en lecture seule.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const candidateId = getRouterParam(event, 'candidateId')

  // L'API répond 204 sans corps : rien à typer ni à relayer, on se contente d'attendre.
  await apiFetchForUser(event, `/v1/searches/${id}/favorites/${candidateId}`, { method: 'DELETE' })

  return null
})
