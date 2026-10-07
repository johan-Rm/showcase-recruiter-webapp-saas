/**
 * Exporter une sélection en CSV (POST /v1/searches/{searchId}/export) : relais.
 *
 * L'API rend un FICHIER et non une ressource sérialisée. On le fait suivre en texte : le CSV en est,
 * et c'est le client qui déclenchera le téléchargement.
 *
 * Le NOM du fichier ne traverse pas ce relais. `apiFetchForUser` rend un corps, pas une réponse —
 * lire les en-têtes de l'API demanderait de dupliquer ici sa logique de session et de rejeu du jeton
 * pour le seul plaisir de récupérer un `content-disposition`. Le client nomme donc le fichier à
 * partir de la recherche, qu'il affiche déjà.
 *
 * L'accusé RGPD voyage dans le corps : c'est l'API qui refuse sans lui. L'écran le demande d'abord,
 * mais ce n'est pas lui qui garde la porte.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const body = await readBody<{ candidateIds?: string[]; gdprAcknowledged?: boolean }>(event)

  // Pas de `responseType` : `apiFetchForUser` type ses options en `json`, et `ofetch` déduit de
  // toute façon le type de la réponse de son `content-type` — `text/csv` lui rend du texte.
  const csv = await apiFetchForUser<string>(event, `/v1/searches/${id}/export`, {
    method: 'POST',
    body: {
      candidateIds: body?.candidateIds ?? [],
      gdprAcknowledged: true === body?.gdprAcknowledged,
    },
  })

  setHeader(event, 'content-type', 'text/csv; charset=utf-8')

  return csv
})
