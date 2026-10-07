import type { SearchProfiles } from '#shared/types/search'

/**
 * Les fiches par lot (GET /v1/searches/profiles) : relais.
 *
 * L'appelant nomme des **recherches** et dit combien de profils prendre de chacune ; l'API rend les
 * fiches, dédoublonnées entre elles. Ni ordre, ni appartenance : l'ordre vient de l'état de travail.
 *
 * Ce chemin est STATIQUE et doit le rester : posé après `[id].get.ts` dans l'ordre de résolution, il
 * serait avalé par la route paramétrée et `profiles` partirait au serveur comme un identifiant de
 * recherche.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<SearchProfiles>(event, '/v1/searches/profiles', {
    query: getQuery(event),
  })
})
