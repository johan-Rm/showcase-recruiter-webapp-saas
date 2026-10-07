import { bffLoginFailure } from '#services/auth/loginFailure'
import { safeRedirectTarget } from '#shared/utils/redirect'

// Action de login : poste au BFF (/api/auth/login), rafraîchit la session, redirige.
export const useLogin = () => {
  const { fetch: refreshSession } = useUserSession()
  const { localePath } = useLocale()
  const route = useRoute()

  const pending = ref(false)
  const error = ref<string | null>(null)

  const login = async (email: string, password: string): Promise<void> => {
    pending.value = true
    error.value = null

    try {
      await $fetch('/api/auth/login', { method: 'POST', body: { email, password } })
      await refreshSession()
      // La cible d'après-login vient de `?redirect` : chemin interne déjà localisé, consommé tel
      // quel (jamais repassé à `localePath`, qui doublerait le préfixe). Absente ou refusée → accueil.
      const target = safeRedirectTarget(route.query.redirect)
      await navigateTo(target ?? localePath('/'))
    } catch (cause) {
      // Un compte suspendu n'est pas une erreur de saisie : le dire évite que la personne
      // réinitialise un mot de passe correct. Le motif vient du BFF (cf. server/api/auth/login).
      error.value =
        'account_suspended' === bffLoginFailure(cause)
          ? 'auth.accountSuspended'
          : 'auth.invalidCredentials'
    } finally {
      pending.value = false
    }
  }

  return { login, pending, error }
}
