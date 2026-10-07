import { fieldViolations } from '#services/validation/violations'

/**
 * Définition du mot de passe depuis le lien reçu après validation admin.
 *
 * Vit dans le socle : le lien atterrit sur une page du socle (`/auth/define-password`), et la même
 * mécanique servira les autres rôles créés par un administrateur.
 *
 * N'ouvre aucune session : succès ⇒ l'appelant redirige vers le login. Les règles restent celles
 * de l'API — ses violations 422 sont exposées champ par champ, jamais réécrites ici. Un lien mort
 * (410) est distingué d'un mot de passe refusé pour proposer de recommencer plutôt que de corriger.
 */
export const usePasswordSetup = () => {
  const pending = ref(false)
  const expired = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string>>({})

  const setupPassword = async (token: string, password: string): Promise<boolean> => {
    pending.value = true
    expired.value = false
    error.value = null
    fieldErrors.value = {}

    try {
      await $fetch('/api/auth/password-setup/consume', {
        method: 'POST',
        body: { token, password },
      })

      return true
    } catch (caught) {
      if ((caught as { statusCode?: number })?.statusCode === 410) {
        expired.value = true

        return false
      }

      fieldErrors.value = fieldViolations(caught)
      if (Object.keys(fieldErrors.value).length === 0) {
        error.value = 'inscription.error.unexpected'
      }

      return false
    } finally {
      pending.value = false
    }
  }

  return { pending, expired, error, fieldErrors, setupPassword }
}
