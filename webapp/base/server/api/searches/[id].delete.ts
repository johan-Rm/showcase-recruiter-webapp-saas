/**
 * Supprimer une recherche (DELETE /v1/searches/{id}) : relais.
 *
 * Réservé à l'AUTEUR — une recherche partagée est en lecture seule pour ses destinataires, qui la
 * dupliquent s'ils veulent la faire leur.
 *
 * DÉFINITIF : l'API efface la recherche, ses profils retenus et sa trace d'export. Il n'y a pas de
 * corbeille, donc pas d'annulation possible après coup — c'est pourquoi l'écran demande une
 * confirmation qui répète le nom.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  // L'API répond 204 sans corps : rien à typer ni à relayer, on se contente d'attendre.
  await apiFetchForUser(event, `/v1/searches/${id}`, { method: 'DELETE' })

  return null
})
