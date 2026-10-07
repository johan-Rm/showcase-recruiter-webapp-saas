import { isSessionExpired } from '#services/auth/sessionFailure'
import { safeRedirectTarget } from '#shared/utils/redirect'

/**
 * Ce que devient l'écran quand le BFF annonce une session close.
 *
 * Deux mémoires portent la connexion — le cookie scellé du navigateur et le compte côté API — et
 * rien ne garantit qu'elles disent la même chose : une base remise à zéro, un compte désactivé ou
 * un rôle révoqué laissent un cookie parfaitement valide en face d'une API qui ne reconnaît plus
 * personne. La garde de navigation, elle, ne lit que le cookie : elle ouvre l'écran, et la panne
 * n'apparaît qu'au premier appel. C'est ici qu'on la rattrape, en fermant la session pour de bon.
 *
 * `clear()` du module de session est le seul geste qui referme le cookie du navigateur, rendu
 * serveur compris — il recopie lui-même son en-tête sur la réponse principale, ce que la purge
 * faite par le BFF depuis un appel interne ne peut pas faire.
 */

// Les écrans d'authentification n'ont pas de session à perdre, et le tunnel d'inscription s'ouvre
// sans rôle : y renvoyer tournerait en boucle. Mêmes exclusions que la garde de navigation.
const AUTH_FREE_PREFIXES = ['login', 'auth-', 'inscription']

export const useSessionExpiry = () => {
  const { clear } = useUserSession()
  const { localePath } = useLocale()
  const route = useRoute()

  // Un écran qui s'ouvre lance plusieurs appels : ils expirent ensemble, et chacun demanderait sa
  // redirection. Le drapeau n'en laisse jouer qu'une, puis se relâche — une session rouverte doit
  // pouvoir se refermer à son tour.
  const closing = useState('auth.session-closing', () => false)

  const baseName = (name: unknown): string => String(name ?? '').replace(/___[a-z]{2}$/, '')

  /**
   * Referme la session du navigateur et rend l'adresse du login à afficher.
   *
   * Rend l'adresse au lieu d'y aller : un middleware de navigation doit **retourner** sa
   * redirection pour qu'elle prenne effet, et c'est de là que vient le contrôle du rendu serveur.
   */
  const closeSession = async (from?: { name: unknown; fullPath: string }) => {
    await clear()

    const origine = from ?? { name: route.name, fullPath: route.fullPath }

    // La cible voyage assainie, comme après un refus de la garde d'accès : elle est déjà localisée,
    // donc jamais repassée à `localePath`, qui doublerait le préfixe. L'accueil n'est pas capturé —
    // y revenir n'apporte rien et alourdit l'URL.
    const target = 'index' === baseName(origine.name) ? null : safeRedirectTarget(origine.fullPath)

    return localePath({
      path: '/login',
      query: { session: 'expired', ...(target ? { redirect: target } : {}) },
    })
  }

  /**
   * Rend `true` si la cause était bien une session close et qu'elle a été traitée — l'appelant n'a
   * alors plus rien à en faire. Rend `false` pour tout le reste, panne réseau et refus de droits
   * compris, que l'appelant continue de gérer comme avant.
   */
  const handleSessionExpiry = async (cause: unknown): Promise<boolean> => {
    if (!isSessionExpired(cause)) {
      return false
    }

    if (closing.value) {
      return true
    }

    closing.value = true

    try {
      if (AUTH_FREE_PREFIXES.some((prefix) => baseName(route.name).startsWith(prefix))) {
        await clear()

        return true
      }

      await navigateTo(await closeSession())

      return true
    } finally {
      closing.value = false
    }
  }

  return { closeSession, handleSessionExpiry }
}
