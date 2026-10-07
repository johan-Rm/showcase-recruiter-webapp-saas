import { ofetch } from 'ofetch'
import { z } from 'zod'

import type { operations } from '#shared/types/api'

type ConsumeResponse =
  operations['api_authpassword-setupconsume_post']['responses'][201]['content']['application/json']

/**
 * Définition du mot de passe après validation admin (`/auth/define-password?token=…`).
 *
 * Le token à usage unique prouve la possession de la boîte : une fois le mot de passe posé, on
 * ouvre la session **dans la foulée** (le déclarant ne ressaisit pas des identifiants qu'il vient
 * de créer). L'API renvoie l'e-mail du compte ; on rejoue le vrai `login` (le firewall émet JWT +
 * refresh token, comme une connexion classique) et on scelle la session en cookie httpOnly.
 *
 * Les erreurs de l'API sont relayées telles quelles : 410 quand le lien est mort (déjà utilisé ou
 * expiré), 422 avec ses violations quand le mot de passe est refusé — le formulaire les affiche.
 */
const bodySchema = z.object({
  token: z.string().min(1),
  password: z.string().min(1),
})

export default defineEventHandler(async (event) => {
  const { token, password } = await readValidatedBody(event, bodySchema.parse)

  let consumed: ConsumeResponse
  try {
    consumed = await ofetch<ConsumeResponse>('/v1/auth/password-setup/consume', {
      baseURL: apiBaseUrl(),
      method: 'POST',
      body: { token, password },
    })
  } catch (error) {
    // La 422 porte les violations champ par champ ; on les transmet pour l'affichage. La 410
    // (lien mort) devient le signal « recommencez ». Tout le reste est une erreur générique.
    throw createError({
      statusCode: (error as { status?: number })?.status ?? 500,
      data: (error as { data?: unknown })?.data,
    })
  }

  // Le mot de passe est posé : on connecte via le chemin de login nominal (session durable).
  const login = await apiLogin(consumed.email ?? '', password)

  await replaceUserSession(event, {
    user: {
      email: consumed.email ?? '',
      roles: rolesFromJwt(login.token),
    },
    secure: {
      apiToken: login.token,
      apiRefreshToken: login.refresh_token,
    },
  })

  return { ok: true }
})
