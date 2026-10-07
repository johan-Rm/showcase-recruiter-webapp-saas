import type { components } from '#shared/types/api'

type ProfileReview = components['schemas']['ProfileReview']

/**
 * Déclarer en lot des profils vus, écartés ou remis (POST /v1/searches/{searchId}/reviews) : relais.
 *
 * EN LOT par construction : marquer un profil vu à chaque carte franchie ferait un appel par
 * profil — plusieurs centaines pour un passage complet. Le client accumule et pousse par paquets.
 *
 * L'état est PERSONNEL — il décrit le parcours du recruteur courant, pas la recherche. C'est ce qui
 * permet au destinataire d'une recherche partagée de la trier alors qu'il ne peut ni la modifier ni
 * la favoriser.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const body = await readBody<{ candidateIds?: string[]; action?: string }>(event)

  return await apiFetchForUser<ProfileReview>(event, `/v1/searches/${id}/reviews`, {
    method: 'POST',
    body: {
      candidateIds: body?.candidateIds ?? [],
      action: body?.action ?? '',
    },
  })
})
