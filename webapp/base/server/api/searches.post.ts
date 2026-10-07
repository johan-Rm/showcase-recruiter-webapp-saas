import type { Search, SearchCriterion } from '#shared/types/search'

interface CreateSearchBody {
  name?: string
  criteria?: SearchCriterion[]
  isShared?: boolean
}

/**
 * Créer une recherche (POST /v1/searches) : relais.
 *
 * Une recherche s'auto-enregistre — elle existe dès qu'un critère est posé, il n'y a pas d'action
 * « Enregistrer » côté produit. Sans nom, l'API garde l'auto-nommage actif et le compose depuis les
 * critères, dans leur ordre.
 *
 * L'API recalcule le jeu de résultats dans la foulée : la recherche créée est immédiatement
 * consultable.
 */
export default defineEventHandler(async (event) => {
  const body = await readBody<CreateSearchBody>(event)

  return await apiFetchForUser<Search>(event, '/v1/searches', {
    method: 'POST',
    body: {
      name: body?.name ?? '',
      criteria: body?.criteria ?? [],
      isShared: body?.isShared ?? false,
    },
  })
})
