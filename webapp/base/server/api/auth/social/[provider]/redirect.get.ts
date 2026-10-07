import { isSocialProvider } from '#shared/types/social'

/**
 * Départ du social login : forge l'URL d'autorisation du fournisseur et y envoie le navigateur.
 *
 * Trois secrets à usage unique sont posés en cookie httpOnly et revérifiés au retour :
 * - `state`  — anti-CSRF : sans lui, un tiers pourrait déclencher un retour de callback forgé ;
 * - `nonce`  — anti-rejeu : lie l'`id_token` À CETTE demande, un jeton capté ailleurs ne vaut rien ;
 * - `verifier` (PKCE) — lie l'échange du code à ce navigateur, là où le fournisseur le supporte.
 *
 * `SameSite=lax` et non `strict` : le retour du fournisseur est une navigation venue d'un autre site,
 * `strict` empêcherait le renvoi des cookies et casserait la vérification du `state`.
 */
const COOKIE_TTL_SECONDS = 600

export default defineEventHandler(async (event) => {
  const provider = getRouterParam(event, 'provider') ?? ''

  // Liste fermée, vérifiée AVANT tout appel réseau : pas de fournisseur arbitraire injecté par l'URL.
  if (!isSocialProvider(provider)) {
    throw createError({ statusCode: 404, statusMessage: 'Fournisseur inconnu' })
  }

  if (!socialConfigured(provider)) {
    throw createError({ statusCode: 503, statusMessage: 'Fournisseur non configuré' })
  }

  const endpoints = SOCIAL_ENDPOINTS[provider]
  const { id: clientId } = socialCredentials(provider)

  const state = randomUrlSafe()
  const nonce = randomUrlSafe()

  const cookieOptions = {
    httpOnly: true,
    sameSite: 'lax',
    secure: 'https:' === getRequestURL(event).protocol,
    path: '/',
    maxAge: COOKIE_TTL_SECONDS,
  } as const

  setCookie(event, 'showcase-recruiter-webapp-saas_social_state', state, cookieOptions)
  setCookie(event, 'showcase-recruiter-webapp-saas_social_nonce', nonce, cookieOptions)

  const params = new URLSearchParams({
    client_id: clientId,
    redirect_uri: socialRedirectUri(event, provider),
    response_type: 'code',
    scope: endpoints.scope,
    state,
    nonce,
  })

  if (endpoints.usesPkce) {
    const verifier = randomUrlSafe()

    setCookie(event, 'showcase-recruiter-webapp-saas_social_verifier', verifier, cookieOptions)
    params.set('code_challenge', await pkceChallenge(verifier))
    params.set('code_challenge_method', 'S256')
  }

  return await sendRedirect(event, `${endpoints.authorizationUrl}?${params.toString()}`)
})
