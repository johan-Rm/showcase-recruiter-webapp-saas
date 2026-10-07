import type { PlaceSuggestion, ResolvedAddress } from '#shared/types/places'

/**
 * Autocomplétion d'adresse, côté client — appelle le BFF (`/api/places/*`), jamais Google en direct :
 * la clé reste au serveur et aucun script tiers n'est chargé.
 *
 * Le `sessionToken` regroupe la frappe et la résolution finale en UNE session facturée par Google. On
 * le forge à la première suggestion et on le REMET À ZÉRO après une résolution : la saisie suivante
 * ouvre une nouvelle session. Le composable n'a pas d'état réactif — il ne fait que des appels.
 */
export const usePlaces = () => {
  let sessionToken = ''

  const ensureSession = (): string => {
    if ('' === sessionToken) {
      sessionToken = crypto.randomUUID()
    }

    return sessionToken
  }

  const suggest = async (
    input: string,
  ): Promise<{ configured: boolean; suggestions: PlaceSuggestion[] }> =>
    // Type EXPLICITE : sans lui, `$fetch` infère la réponse depuis le registre de routes Nitro, dont
    // l'union (volumineuse dans l'espace admin) fait exploser la profondeur d'inférence (TS2321).
    await $fetch<{ configured: boolean; suggestions: PlaceSuggestion[] }>(
      '/api/places/autocomplete',
      {
        method: 'POST',
        body: { input, sessionToken: ensureSession() },
      },
    )

  const resolve = async (id: string): Promise<ResolvedAddress> => {
    const address = await $fetch<ResolvedAddress>('/api/places/details', {
      query: { id, session: ensureSession() },
    })
    // Session close : la prochaine saisie repart sur un token neuf, donc une facturation neuve.
    sessionToken = ''

    return address
  }

  return { suggest, resolve }
}

/**
 * Disponibilité de l'autocomplétion : une clé Places est-elle posée côté serveur ? Sert au formulaire
 * pour choisir son mode d'emblée (recherche vs saisie manuelle). Dans un composable, pas dans le
 * composant : un composant ne fait pas de réseau. Résolu au rendu SSR.
 */
export const usePlacesStatus = () => {
  // Type EXPLICITE (comme `suggest`) : coupe l'inférence de route qui explose côté admin.
  const { data } = useFetch<{ configured: boolean }>('/api/places/status')

  return computed<boolean>(() => data.value?.configured ?? false)
}
