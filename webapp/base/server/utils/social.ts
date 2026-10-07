/**
 * Social login (OIDC) — points d'accès des fournisseurs et secrets, côté SERVEUR uniquement.
 *
 * Le BFF mène la danse OAuth ; le `client_secret` ne quitte jamais Nitro, comme la clé Places. Le
 * navigateur ne fait que suivre une redirection : aucun SDK fournisseur n'est chargé dans la page
 * (RGPD + CSP inchangés).
 *
 * Ce module ne PROUVE rien : il obtient un `id_token` et le présente à l'API, qui seule vérifie sa
 * signature contre le JWKS du fournisseur. Un BFF compromis ne peut donc pas faire
 * envoyer un magic link à une adresse arbitraire.
 */
import type { SocialProvider } from '#shared/types/social'

interface ProviderEndpoints {
  authorizationUrl: string
  tokenUrl: string
  scope: string
  /**
   * PKCE protège l'échange du `code` contre son interception. Google le supporte pour les clients
   * web ; LinkedIn ne le documente pas — envoyer un `code_verifier` qu'il ignore ferait échouer
   * l'échange chez certains fournisseurs, donc on ne l'active que là où il est établi.
   */
  usesPkce: boolean
}

export const SOCIAL_ENDPOINTS: Record<SocialProvider, ProviderEndpoints> = {
  google: {
    authorizationUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
    tokenUrl: 'https://oauth2.googleapis.com/token',
    scope: 'openid email profile',
    usesPkce: true,
  },
  linkedin: {
    authorizationUrl: 'https://www.linkedin.com/oauth/v2/authorization',
    tokenUrl: 'https://www.linkedin.com/oauth/v2/accessToken',
    scope: 'openid profile email',
    usesPkce: false,
  },
}

/**
 * Identifiants d'un fournisseur. Garde `typeof` défensif, comme pour Places : une valeur absente du
 * runtimeConfig retombe sur vide — donc « non configuré » — plutôt que d'appeler le fournisseur avec
 * une valeur douteuse.
 */
export const socialCredentials = (provider: SocialProvider): { id: string; secret: string } => {
  const config = useRuntimeConfig()
  const raw = provider === 'google' ? config.googleOidc : config.linkedinOidc

  return {
    id: 'string' === typeof raw?.clientId ? raw.clientId : '',
    secret: 'string' === typeof raw?.clientSecret ? raw.clientSecret : '',
  }
}

/**
 * Un fournisseur n'est utilisable que si les DEUX identifiants sont posés : l'`id` construit l'URL
 * d'autorisation, le `secret` échange le code. L'un sans l'autre mène à une erreur au retour — mieux
 * vaut ne pas afficher le bouton du tout.
 */
export const socialConfigured = (provider: SocialProvider): boolean => {
  const { id, secret } = socialCredentials(provider)

  return '' !== id && '' !== secret
}

/**
 * URI de redirection, dérivée de l'hôte réellement servi — un espace par host, et le dev, la préprod
 * et la prod n'ont pas le même. La dériver évite une variable d'environnement de plus par
 * environnement, à re-synchroniser à chaque fois.
 *
 * Un `Host` falsifié ne donne rien : le fournisseur compare cette URI à sa liste blanche et rejette
 * tout ce qui n'y figure pas. C'est SA validation qui fait la sécurité ici, pas la nôtre.
 */
export const socialRedirectUri = (
  event: Parameters<typeof getRequestURL>[0],
  provider: string,
): string =>
  new URL(`/api/auth/social/${provider}/callback`, getRequestURL(event).origin).toString()

/** Valeur aléatoire URL-safe pour `state`, `nonce` et le `code_verifier` PKCE. */
export const randomUrlSafe = (bytes = 32): string =>
  Buffer.from(crypto.getRandomValues(new Uint8Array(bytes))).toString('base64url')

/** `code_challenge` PKCE = SHA-256 du verifier, en base64url (méthode S256). */
export const pkceChallenge = async (verifier: string): Promise<string> => {
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier))

  return Buffer.from(digest).toString('base64url')
}
