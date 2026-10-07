import type { SearchWorkspace } from '#shared/types/search'

/**
 * Le plan de travail du recruteur (GET /v1/searches/workspace) : relais.
 *
 * Toutes ses recherches OUVERTES en une réponse — leurs jeux, leur état de travail, et les fiches
 * dédoublonnées. Un profil qui sert trois recherches n'y voyage qu'une fois, ce qui est la seule
 * raison pour laquelle cette route tient dans une réponse.
 *
 * Aucun paramètre : le périmètre est celui de la session, et il n'y a rien à choisir.
 *
 * Ce chemin est STATIQUE et doit le rester : posé après `[id].get.ts` dans l'ordre de résolution, il
 * serait avalé par la route paramétrée et `workspace` partirait au serveur comme un identifiant de
 * recherche.
 */
export default defineEventHandler(async (event) => {
  return await apiFetchForUser<SearchWorkspace>(event, '/v1/searches/workspace')
})
