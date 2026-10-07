import type { components } from '#shared/types/api'

type LabelReview = components['schemas']['LabelReview']

/**
 * Relecture d'un LIBELLÉ « mot à mot » : relais de
 * POST /v1/admin/referentials/labels/reviews.
 *
 * Déprécier = exclure de l'élargissement (réversible) ; CANDIDAT = restaurer. La vérité métier
 * (statuts acceptés, traçabilité) reste côté API ; le BFF relaie ses 422 tels quels.
 */
export default defineEventHandler(async (event) => {
  const body = await readBody<{ labelIds: string[]; status: string }>(event)

  return await apiFetchForUser<LabelReview>(event, '/v1/admin/referentials/labels/reviews', {
    method: 'POST',
    body,
  })
})
