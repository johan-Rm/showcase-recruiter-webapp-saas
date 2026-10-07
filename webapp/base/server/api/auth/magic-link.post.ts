import { ofetch } from 'ofetch'
import { z } from 'zod'

import type { operations } from '#shared/types/api'

// Type explicite : sans lui, `$fetch` tente d'inférer la route parmi celles de Nitro et l'inférence
// explose (TS2321, « Excessive stack depth ») dès que l'espace embarque assez de routes serveur.
type MagicLinkRequestResponse =
  operations['api_authmagic-linkrequest_post']['responses'][201]['content']['application/json']

/**
 * Demande d'un magic link — tunnel d'inscription entreprise.
 *
 * Public : aucune session requise. L'API répond la même chose que l'adresse existe ou non —
 * on relaie tel quel, sans jamais laisser filtrer l'existence d'un compte.
 */
const bodySchema = z.object({
  email: z.string().email(),
})

export default defineEventHandler(async (event) => {
  const { email } = await readValidatedBody(event, bodySchema.parse)

  // L'espace de CETTE build décide du sous-domaine vers lequel l'API fera pointer le lien : un
  // déclarant d'entreprise ne doit pas atterrir sur l'espace candidat, et inversement.
  const { space } = useRuntimeConfig().public

  return await ofetch<MagicLinkRequestResponse>('/v1/auth/magic-link/request', {
    baseURL: apiBaseUrl(),
    method: 'POST',
    body: { email, space },
  })
})
