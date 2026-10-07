import type { Search, SearchCriterion } from '#shared/types/search'

interface UpdateSearchBody {
  name?: string
  criteria?: SearchCriterion[]
  isShared?: boolean
  /** Déclarer le travail terminé, ou le rouvrir. */
  isClosed?: boolean
}

/**
 * Modifier une recherche (PATCH /v1/searches/{id}) : relais.
 *
 * Réservé à l'AUTEUR — une recherche partagée est en lecture seule, son destinataire la duplique. Et
 * l'API recalcule le jeu de résultats dans la foulée : changer un critère rejoue la recherche.
 *
 * Seules les clés présentes sont envoyées : un PATCH qui poserait `name: ''` sans qu'on l'ait
 * demandé réactiverait l'auto-nommage au passage. Le revers de ce filtrage : une clé oubliée ici
 * est jetée EN SILENCE et la route répond quand même 200. `isClosed` a passé plusieurs écrans dans
 * ce trou, servi par l'API et jamais relayé.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const body = await readBody<UpdateSearchBody>(event)

  const payload: UpdateSearchBody = {}
  if (undefined !== body?.name) {
    payload.name = body.name
  }
  if (undefined !== body?.criteria) {
    payload.criteria = body.criteria
  }
  if (undefined !== body?.isShared) {
    payload.isShared = body.isShared
  }
  if (undefined !== body?.isClosed) {
    payload.isClosed = body.isClosed
  }

  return await apiFetchForUser<Search>(event, `/v1/searches/${id}`, {
    method: 'PATCH',
    body: payload,
  })
})
