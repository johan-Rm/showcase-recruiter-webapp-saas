/**
 * Magic link — demande et consommation.
 *
 * Vit dans le socle : les deux espaces qui authentifient par lien (candidat, entreprise) l'utilisent,
 * et l'atterrissage (`/auth/magic-link`) est une page du socle.
 *
 * Effets : `requestLink` envoie un e-mail ; `consumeLink` OUVRE une session (cookie scellé par le
 * BFF). Rien n'est déclenché à l'invocation — l'appelant décide.
 */
export const useMagicLink = () => {
  const pending = ref(false)
  const failed = ref(false)

  const call = async (path: string, body: Record<string, string>): Promise<boolean> => {
    pending.value = true
    failed.value = false

    try {
      await $fetch(path, { method: 'POST', body })

      return true
    } catch {
      failed.value = true

      return false
    } finally {
      pending.value = false
    }
  }

  const requestLink = async (email: string): Promise<boolean> =>
    await call('/api/auth/magic-link', { email })

  const consumeLink = async (token: string): Promise<boolean> =>
    await call('/api/auth/magic-link/consume', { token })

  return { pending, failed, requestLink, consumeLink }
}
