import { ofetch } from 'ofetch'

import { isSocialProvider } from '#shared/types/social'

/**
 * Retour du fournisseur : échange le `code` contre un `id_token`, puis demande à l'API d'émettre un
 * magic link vers l'e-mail que ce jeton prouve.
 *
 * **Le BFF ne prouve rien.** Il n'a pas les clés du fournisseur : c'est l'API qui vérifie la signature
 * contre le JWKS, l'émetteur, l'audience et `email_verified`. Ici on ne contrôle que ce
 * que l'API ne PEUT pas contrôler, faute d'état : le `state` (anti-CSRF) et le `nonce` (anti-rejeu).
 * Les deux moitiés réunies couvrent la vérification OIDC complète.
 *
 * Quoi qu'il arrive on repart vers l'écran d'inscription — jamais une page blanche ni une trace
 * technique : succès → « e-mail envoyé », échec → le même écran avec un message.
 */
const RETURN_PATH = '/login'

const backTo = (event: Parameters<typeof sendRedirect>[0], query: string): Promise<void> =>
  sendRedirect(event, `${RETURN_PATH}?inscription&${query}`)

/** Efface les trois cookies à usage unique : ils ne servent qu'à CE passage. */
const clearHandshakeCookies = (event: Parameters<typeof deleteCookie>[0]): void => {
  for (const name of ['showcase-recruiter-webapp-saas_social_state', 'showcase-recruiter-webapp-saas_social_nonce', 'showcase-recruiter-webapp-saas_social_verifier']) {
    deleteCookie(event, name, { path: '/' })
  }
}

/**
 * Lit le `nonce` du corps de l'`id_token` SANS vérifier la signature — impossible ici, les clés sont
 * chez le fournisseur. Ce n'est pas un trou : le jeton part ensuite à l'API, qui le rejette s'il est
 * forgé. Un attaquant peut donc fabriquer un corps au bon `nonce`, il ne passera pas l'API.
 */
const nonceOf = (idToken: string): string => {
  const payload = idToken.split('.').at(1)
  if (!payload) return ''

  try {
    const claims = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as {
      nonce?: string
    }

    return claims.nonce ?? ''
  } catch {
    return ''
  }
}

export default defineEventHandler(async (event) => {
  const provider = getRouterParam(event, 'provider') ?? ''

  if (!isSocialProvider(provider)) {
    throw createError({ statusCode: 404, statusMessage: 'Fournisseur inconnu' })
  }

  const query = getQuery(event)
  const expectedState = getCookie(event, 'showcase-recruiter-webapp-saas_social_state') ?? ''
  const expectedNonce = getCookie(event, 'showcase-recruiter-webapp-saas_social_nonce') ?? ''
  const verifier = getCookie(event, 'showcase-recruiter-webapp-saas_social_verifier') ?? ''

  clearHandshakeCookies(event)

  // Consentement refusé : ce n'est pas une panne, l'utilisateur a dit non. On le ramène simplement.
  if (query.error) {
    return await backTo(event, 'social=cancelled')
  }

  const code = String(query.code ?? '')
  const state = String(query.state ?? '')

  // `state` absent des cookies = pas de demande en cours : on refuse plutôt que de comparer du vide
  // à du vide, ce qui laisserait passer un callback forgé.
  if ('' === code || '' === expectedState || state !== expectedState) {
    return await backTo(event, 'social=failed')
  }

  try {
    const endpoints = SOCIAL_ENDPOINTS[provider]
    const { id: clientId, secret: clientSecret } = socialCredentials(provider)

    const body = new URLSearchParams({
      grant_type: 'authorization_code',
      code,
      client_id: clientId,
      client_secret: clientSecret,
      redirect_uri: socialRedirectUri(event, provider),
    })

    if (endpoints.usesPkce && '' !== verifier) {
      body.set('code_verifier', verifier)
    }

    const tokens = await ofetch<{ id_token?: string }>(endpoints.tokenUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    })

    const idToken = tokens.id_token ?? ''
    if ('' === idToken) {
      throw new Error('Réponse sans id_token')
    }

    // Le jeton doit répondre à CETTE demande : un `nonce` qui ne correspond pas signale un rejeu.
    if ('' === expectedNonce || nonceOf(idToken) !== expectedNonce) {
      throw new Error('Nonce inattendu')
    }

    // L'espace appelant décide du sous-domaine visé par le lien, comme la demande par e-mail.
    const { space } = useRuntimeConfig().public

    await ofetch('/v1/auth/social/magic-link', {
      baseURL: apiBaseUrl(),
      method: 'POST',
      body: { provider, idToken, space },
    })
  } catch (error) {
    // Tracé côté serveur : sans ça, une clé mal configurée ressemble à un refus de l'utilisateur.
    // Jamais le secret ni le jeton — seulement le message.
    console.error(
      `[social] échange ${provider} échoué :`,
      error instanceof Error ? error.message : error,
    )

    return await backTo(event, 'social=failed')
  }

  return await backTo(event, 'social=sent')
})
