import type { SearchSuggestionCollection } from '#shared/types/search'

/**
 * Ce qu'on propose pendant la frappe (GET /v1/suggestions?type=JOB|SKILL&q=…) : relais.
 *
 * Les propositions sont tirées de ce que les profils portent RÉELLEMENT : aucune ne ramène zéro, et
 * chacune annonce combien elle ramène. L'API réserve la route aux recruteurs d'une organisation
 * validée — elle décrit le vivier, elle n'est pas publique.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<SearchSuggestionCollection>(event, '/v1/suggestions', {
    query: getQuery(event),
  })
})
