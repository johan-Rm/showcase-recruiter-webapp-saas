import { ofetch } from 'ofetch'

import { SESSION_EXPIRED } from '#services/auth/sessionFailure'

import type { FetchOptions } from 'ofetch'

import type { H3Event } from 'h3'

import type { operations } from '#shared/types/api'

/**
 * Client serveur → API Symfony. Le BFF attache le JWT de la session utilisateur
 * (jamais de token de service — l'API applique l'autorisation de l'utilisateur).
 * Réf : architecture.md §5 (BFF nuxt-auth-utils).
 */

// Dérivés du contrat OpenAPI (shared/types/api.ts, généré) — jamais écrits à la main.
type LoginResponse = operations['login_check_post']['responses'][200]['content']['application/json']
type RefreshResponse =
  operations['refresh_token_post']['responses'][200]['content']['application/json']

/**
 * Refresh en vol, par requête. Un rendu SSR peut lancer plusieurs appels API en parallèle :
 * tous expireraient ensemble et déclencheraient chacun un refresh. La clé est l'événement —
 * pas le jeton — pour que la session mise à jour soit bien celle de CETTE requête.
 */
const pendingRefresh = new WeakMap<H3Event, Promise<string>>()

export const apiBaseUrl = (): string => {
  const config = useRuntimeConfig()

  return config.apiInternalBase || config.public.apiBase
}

export const apiLogin = async (email: string, password: string): Promise<LoginResponse> => {
  return await ofetch<LoginResponse>('/v1/auth/login', {
    baseURL: apiBaseUrl(),
    method: 'POST',
    body: { email, password },
  })
}

/**
 * L'organisation du recruteur connecté, résolue côté SERVEUR.
 *
 * Tout ce qui touche aux crédits et au dévoilement est adressé par organisation. L'identifiant reste
 * ici plutôt que de voyager jusqu'au client : le navigateur n'a aucune raison de choisir l'organisation
 * qu'il débite, et un relais qui accepterait la sienne accepterait aussi celle d'un autre.
 *
 * Mémorisé PAR REQUÊTE : un rendu qui lit le solde et la liste des dévoilés ne demande `/v1/me`
 * qu'une fois. Pas au-delà — le rattachement d'un recruteur peut changer, et une session vit
 * longtemps.
 */
const organizationIds = new WeakMap<H3Event, Promise<string | null>>()

export const organizationIdFor = async (event: H3Event): Promise<string | null> => {
  const known = organizationIds.get(event)
  if (known) {
    return await known
  }

  const resolving = apiFetchForUser<{ organizationId?: string | null }>(event, '/v1/me')
    .then((me) => me.organizationId ?? null)
    .catch(() => null)

  organizationIds.set(event, resolving)

  return await resolving
}

/**
 * La même chose, mais exigeante : un appel qui a besoin d'une organisation n'a rien à faire sans.
 *
 * 403 et non 404 : la ressource existe, c'est l'appelant qui n'a pas de casquette pour l'atteindre —
 * un candidat, ou un recruteur désactivé.
 */
export const requireOrganizationId = async (event: H3Event): Promise<string> => {
  const organizationId = await organizationIdFor(event)

  if (!organizationId) {
    throw createError({ statusCode: 403, statusMessage: 'No active recruiter organization' })
  }

  return organizationId
}

/** Rôles lus dans le payload du JWT Lexik (non vérifié : l'API reste juge). */
export const rolesFromJwt = (token: string): string[] => {
  const payload = token.split('.').at(1)
  if (!payload) {
    return []
  }

  try {
    const decoded = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as {
      roles?: string[]
    }

    return decoded.roles ?? []
  } catch {
    return []
  }
}

/**
 * Secondes restant avant l'`exp` du JWT (0 si illisible ou déjà passé).
 *
 * Sert à faire expirer le cookie d'une session SANS refresh token en même temps que son JWT : sans
 * cela le cookie lui survit, la session paraît vivante (la garde du tunnel affiche le formulaire)
 * alors qu'aucun appel API ne passe plus, et l'expiration ne se découvre qu'au premier 401
 * (cf. `apiFetchForUser`, qui la masque en « erreur inattendue » faute de refresh token).
 */
export const jwtSecondsUntilExpiry = (token: string): number => {
  const payload = token.split('.').at(1)
  if (!payload) {
    return 0
  }

  try {
    const decoded = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as {
      exp?: number
    }

    return decoded.exp ? Math.max(0, decoded.exp - Math.floor(Date.now() / 1000)) : 0
  } catch {
    return 0
  }
}

const isUnauthorized = (error: unknown): boolean =>
  error instanceof Error && 'statusCode' in error && Number(error.statusCode) === 401

const callApi = async <TResponse>(
  path: string,
  token: string,
  options: FetchOptions<'json'>,
): Promise<TResponse> => {
  // `Headers` plutôt qu'un littéral étalé : `FetchOptions.headers` accepte trois formes (objet,
  // tuples, `Headers`), et les étaler produit une union que TypeScript refuse.
  const headers = new Headers(options?.headers)
  headers.set('Authorization', `Bearer ${token}`)

  return await ofetch<TResponse>(path, { ...options, baseURL: apiBaseUrl(), headers })
}

/**
 * Ferme une session qui ne peut plus servir, et le DIT.
 *
 * Le motif est nommé plutôt que laissé au seul code 401 : l'écran doit distinguer « ta session est
 * close » — où il n'y a rien d'autre à faire que se reconnecter — d'un refus de droits ou d'une
 * panne, qui ne doivent déconnecter personne. La purge côté serveur ne suffit pas à elle seule :
 * émise depuis un appel interne au rendu SSR, elle n'atteint pas le cookie du navigateur. C'est
 * `useSessionExpiry`, côté application, qui referme la session pour de bon.
 */
const sessionExpired = async (event: H3Event): Promise<never> => {
  await clearUserSession(event)

  throw createError({
    statusCode: 401,
    statusMessage: 'Session expirée',
    data: { reason: SESSION_EXPIRED },
  })
}

/**
 * Échange le refresh token contre un JWT neuf et réécrit la session scellée.
 *
 * Les rôles sont relus depuis le nouveau JWT : un rôle révoqué côté API doit se propager,
 * sinon la session porterait indéfiniment les droits du login initial.
 *
 * `replaceUserSession` et non `setUserSession` : ce dernier FUSIONNE, et les tableaux se
 * concatènent — les rôles se dupliquaient à chaque refresh, et un rôle révoqué survivait à la
 * fusion. Exactement l'inverse du but recherché.
 */
const refreshApiToken = async (event: H3Event, refreshToken: string): Promise<string> => {
  const refreshed = await ofetch<RefreshResponse>('/v1/auth/refresh', {
    baseURL: apiBaseUrl(),
    method: 'POST',
    body: { refresh_token: refreshToken },
  })

  const session = await requireUserSession(event)

  await replaceUserSession(event, {
    user: { ...session.user, roles: rolesFromJwt(refreshed.token) },
    secure: {
      apiToken: refreshed.token,
      apiRefreshToken: refreshed.refresh_token,
    },
  })

  return refreshed.token
}

const refreshOnce = (event: H3Event, refreshToken: string): Promise<string> => {
  const inFlight = pendingRefresh.get(event)

  if (inFlight) {
    return inFlight
  }

  const promise = refreshApiToken(event, refreshToken).finally(() => pendingRefresh.delete(event))
  pendingRefresh.set(event, promise)

  return promise
}

/**
 * Appel de l'API Symfony avec le Bearer de la session courante.
 *
 * `ofetch` et non `$fetch` : ce dernier est typé sur les routes INTERNES de Nitro. Pointé vers une
 * URL externe, son inférence explose (TS2321) — et le contrat est de toute façon porté par les types
 * générés depuis l'OpenAPI.
 *
 * Sur `401`, le JWT (1 h) est rejoué via le refresh token (30 j) et la requête est relancée
 * **une seule fois** : un second 401 avec un jeton frais ne vient pas d'une expiration, le
 * rejouer boucleraient. Si le refresh échoue, la session est fermée et l'appelant reçoit un 401
 * portant le motif `session_expired` (cf. `sessionExpired`).
 */
export const apiFetchForUser = async <TResponse>(
  event: H3Event,
  path: string,
  options: FetchOptions<'json'> = {},
): Promise<TResponse> => {
  const session = await requireUserSession(event)

  try {
    return await callApi<TResponse>(path, session.secure?.apiToken ?? '', options)
  } catch (error) {
    if (!isUnauthorized(error)) {
      throw error
    }

    const refreshToken = session.secure?.apiRefreshToken

    if (!refreshToken) {
      return await sessionExpired(event)
    }

    try {
      const freshToken = await refreshOnce(event, refreshToken)

      return await callApi<TResponse>(path, freshToken, options)
    } catch (retryError) {
      if (isUnauthorized(retryError)) {
        return await sessionExpired(event)
      }

      throw retryError
    }
  }
}
