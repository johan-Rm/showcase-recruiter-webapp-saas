import { ofetch } from 'ofetch'
import { z } from 'zod'

import type { operations } from '#shared/types/api'

type ConsumeResponse =
  operations['api_authmagic-linkconsume_post']['responses'][201]['content']['application/json']

/**
 * Consommation du magic link : échange le token à usage unique contre une session
 * BFF scellée. Le JWT ne quitte jamais le serveur, comme au login.
 *
 * L'API ne délivre **pas** de refresh token sur ce chemin : la session du déclarant vit le temps
 * du JWT (1 h), ce qui couvre largement l'inscription. Elle expirera ensuite proprement (401 →
 * /login), au lieu de se prolonger indéfiniment sur une preuve à usage unique.
 */
const bodySchema = z.object({
  token: z.string().min(1),
})

export default defineEventHandler(async (event) => {
  const { token } = await readValidatedBody(event, bodySchema.parse)

  let consumed: ConsumeResponse
  try {
    consumed = await ofetch<ConsumeResponse>('/v1/auth/magic-link/consume', {
      baseURL: apiBaseUrl(),
      method: 'POST',
      body: { token },
    })
  } catch {
    throw createError({ statusCode: 401, statusMessage: 'Magic link invalide ou expiré' })
  }

  const apiToken = consumed.token ?? ''

  // La session déclarant n'a pas de refresh token : passé l'`exp` du JWT, aucun appel API ne passe
  // plus. On fait donc EXPIRER le cookie de session AVEC le JWT. Sans ça le cookie lui survit (le
  // défaut nuxt-auth-utils ne fixe aucun maxAge → cookie de session sans échéance) : la garde du
  // tunnel prend la session pour vivante et affiche le formulaire, et l'expiration ne se révèle qu'au
  // submit — un 401 masqué en « erreur inattendue ». `|| 3600` : garde-fou si l'`exp` est illisible,
  // pour ne pas sceller un cookie déjà expiré.
  const maxAge = jwtSecondsUntilExpiry(apiToken) || 3600

  await replaceUserSession(
    event,
    {
      user: {
        email: consumed.email ?? '',
        roles: rolesFromJwt(apiToken),
      },
      secure: { apiToken },
    },
    { maxAge },
  )

  return { ok: true }
})
