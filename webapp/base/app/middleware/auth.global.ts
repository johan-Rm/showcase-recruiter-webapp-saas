import { isSessionExpired } from '#services/auth/sessionFailure'
import { SPACE_ROLE_PREFIX } from '#shared/utils/space'

/**
 * Garde d'auth des espaces SaaS (candidat / entreprise / admin) : session
 * requise et rôle aligné sur l'espace de la build (un candidat connecté sur
 * la build entreprise est renvoyé vers /login).
 *
 * L'existence des pages par espace n'est plus gardée ici : elle est
 * structurelle (une page absente de la layer d'espace → 404 natif).
 */
// `auth-magic-link` : l'atterrissage du lien OUVRE la session — l'exiger avant serait circulaire.
// `auth-define-password` : lien reçu par e-mail après validation admin, avant tout mot de passe —
// le déclarant n'a pas de session et n'en ouvre pas ici (il ira se connecter ensuite).
const AUTH_FREE_ROUTES = new Set(['login', 'auth-magic-link', 'auth-define-password'])

/**
 * Le tunnel d'inscription entreprise est public par nature : le déclarant n'a pas encore de
 * rôle entreprise (il l'obtient EN créant l'entreprise). Le passer à la garde de rôle
 * ci-dessous le renverrait au login — soit une inscription impossible. Chaque écran du tunnel
 * gère lui-même l'état de session qu'il exige.
 */
const AUTH_FREE_PREFIX = 'inscription'

export default defineNuxtRouteMiddleware(async (to) => {
  const space = useSpace()

  if (space === 'public') {
    return
  }

  const routeBaseName = String(to.name ?? '').replace(/___[a-z]{2}$/, '')

  const { localePath } = useLocale()
  const { loggedIn, user } = useUserSession()
  const hasSpaceRole = (user.value?.roles ?? []).some((role) =>
    role.startsWith(SPACE_ROLE_PREFIX[space]),
  )

  // Le tunnel d'inscription reste ouvert au déclarant sans rôle d'espace (il l'obtient EN créant
  // l'entreprise). Mais un membre déjà inscrit n'y a plus rien à faire : sa place est dans l'espace.
  if (routeBaseName.startsWith(AUTH_FREE_PREFIX)) {
    // Un token en query = atterrissage de magic link : il PRIME sur la session en cours (il va la
    // remplacer). On ne renvoie donc PAS un membre déjà inscrit vers son espace — sinon le lien serait
    // court-circuité avant même d'être lu. L'écran cible déconnecte puis consomme le token.
    if (to.query.token) {
      return
    }

    return hasSpaceRole ? navigateTo(localePath('/')) : undefined
  }

  if (AUTH_FREE_ROUTES.has(routeBaseName)) {
    return
  }

  // Le cookie dit qui PRÉTEND être connecté, pas si l'API l'honore encore : une base remise à zéro,
  // un compte désactivé, un rôle révoqué le laissent parfaitement valide. Sans le contrôle qui suit,
  // l'écran s'ouvre au nom de quelqu'un que l'API ne connaît plus, et n'affiche que ses erreurs de
  // chargement.
  if (loggedIn.value && hasSpaceRole && import.meta.server) {
    // Au rendu serveur seulement : c'est là que l'écran est servi d'un bloc, sans qu'aucun appel ne
    // reparte ensuite du navigateur pour découvrir le refus. Après l'affichage, les appels sont
    // enveloppés (`plugins/session-expiry.client.ts`) et il n'y a rien à contrôler d'avance.
    try {
      await useRequestFetch()('/api/me')
    } catch (cause) {
      if (isSessionExpired(cause)) {
        return navigateTo(await useSessionExpiry().closeSession(to))
      }
      // Une API en panne n'est pas une session close : on laisse entrer, l'écran dira ce qu'il ne
      // peut pas charger. Refuser ici transformerait une panne passagère en déconnexion.
    }
  }

  // Espace protégé : session requise ET rôle aligné sur l'espace de la build.
  if (!loggedIn.value || !hasSpaceRole) {
    // On mémorise la cible pour y revenir après connexion (cf. useLogin). `to.fullPath` porte déjà
    // le préfixe de locale et la query : il voyage tel quel dans `?redirect`, jamais repassé à
    // `localePath`. La racine n'est pas capturée : y revenir n'apporte rien et alourdit l'URL.
    const target = routeBaseName === 'index' ? undefined : to.fullPath

    return navigateTo(
      localePath(target ? { path: '/login', query: { redirect: target } } : '/login'),
    )
  }
})
