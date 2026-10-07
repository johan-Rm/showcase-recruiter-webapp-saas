import type { CandidateCardCollection } from '#shared/types/candidate-card'

/**
 * Le jeu de résultats d'une recherche (GET /v1/searches/{id}/results) : relais.
 *
 * L'API sert le jeu qu'elle a MATÉRIALISÉ pour cette recherche — le front ne rejoue pas les critères,
 * il n'y aurait sinon aucune garantie que les deux vues du même jeu concordent. Chaque carte arrive
 * avec son état de travail (nouveau, favori, shortlisté) et son masquage déjà appliqué.
 *
 * Ce GET remet aussi à jour le repère de lecture côté API : ouvrir une recherche, c'est regarder ses
 * résultats.
 */
export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  return await apiFetchForUser<CandidateCardCollection>(event, `/v1/searches/${id}/results`, {
    query: getQuery(event),
  })
})
