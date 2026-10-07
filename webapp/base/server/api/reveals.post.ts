import type { RevealType } from '#shared/types/reveal'

/**
 * Dévoiler un ou plusieurs profils : relais vers l'API, qui débite les crédits.
 *
 * UNE SEULE route pour l'unité et le lot — l'API en expose deux, mais elles répondent à la même
 * intention et la seconde n'est que la première répétée. Le client envoie une liste ; à un seul
 * élément, on prend la route unitaire, qui n'impose pas de plafond.
 *
 * L'ORGANISATION N'EST PAS DANS LE CORPS : elle est résolue côté serveur depuis la session. Un
 * relais qui accepterait celle du client accepterait aussi celle d'un autre.
 */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)
  const body = await readBody<{ candidateIds?: string[]; type?: RevealType }>(event)
  const candidateIds = body?.candidateIds ?? []
  const type = body?.type

  if (0 === candidateIds.length || !type) {
    throw createError({ statusCode: 422, statusMessage: 'candidateIds and type are required' })
  }

  const base = `/v1/organizations/${organizationId}/revealed-profiles`

  if (1 === candidateIds.length) {
    return await apiFetchForUser(event, base, {
      method: 'POST',
      body: { candidateId: candidateIds[0], type },
    })
  }

  return await apiFetchForUser(event, `${base}/batch`, {
    method: 'POST',
    body: { candidateIds, type },
  })
})
