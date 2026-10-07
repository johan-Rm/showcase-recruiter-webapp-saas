import { z } from 'zod'

import { apiLoginFailure } from '#services/auth/loginFailure'

/**
 * Login BFF : délègue à l'API Symfony (/v1/auth/login) puis pose la session
 * scellée en cookie httpOnly. Le JWT et le refresh token restent côté serveur
 * (zone `secure` de la session), seuls email + rôles sont visibles du client.
 */
const bodySchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
})

export default defineEventHandler(async (event) => {
  const { email, password } = await readValidatedBody(event, bodySchema.parse)

  let login: Awaited<ReturnType<typeof apiLogin>>
  try {
    login = await apiLogin(email, password)
  } catch (cause) {
    // Un compte suspendu et un mot de passe faux se ressemblent — même code, même écran. Sans ce
    // relais du motif, la personne conclurait qu'elle s'est trompée et irait réinitialiser un mot
    // de passe correct. L'API le nomme (`account_suspended`), le BFF le transporte, l'écran le dit.
    throw createError({
      statusCode: 401,
      statusMessage: 'Invalid credentials',
      data: { reason: apiLoginFailure(cause) },
    })
  }

  // `replaceUserSession` : `setUserSession` fusionne, et une reconnexion sans logout préalable
  // concaténerait les rôles de l'ancienne session avec ceux de la nouvelle.
  await replaceUserSession(event, {
    user: {
      email,
      roles: rolesFromJwt(login.token),
    },
    secure: {
      apiToken: login.token,
      apiRefreshToken: login.refresh_token,
    },
  })

  return { ok: true }
})
