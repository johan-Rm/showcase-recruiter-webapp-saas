import type { components } from '#shared/types/api'

type ReferentialReview = components['schemas']['ReferentialReview']

/**
 * Relecture d'un référentiel : relais de POST /v1/admin/referentials/reviews.
 *
 * Le corps n'est pas revalidé ici : la vérité métier est celle de l'API (statut limité à
 * VALIDE / DEPRECIE, traçabilité du relecteur). Le BFF relaie ses 422 tels quels.
 */
export default defineEventHandler(async (event) => {
  const body = await readBody<{ codes: string[]; status: string }>(event)

  return await apiFetchForUser<ReferentialReview>(event, '/v1/admin/referentials/reviews', {
    method: 'POST',
    body,
  })
})
