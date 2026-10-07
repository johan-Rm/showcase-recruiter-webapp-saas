/**
 * Google Places (API New) — accès clé et mapping d'adresse, côté SERVEUR uniquement.
 *
 * La clé ne quitte jamais le serveur : le BFF (server/api/places/*) proxie l'autocomplétion, comme il
 * proxie déjà l'API métier avec le JWT. Aucun script Google n'est chargé côté client — les frappes
 * transitent client → BFF → Google, pas de tiers injecté dans la page (RGPD + CSP inchangés).
 *
 * Sans clé configurée, tout ici répond « non configuré » : l'appelant (le formulaire) retombe alors
 * en saisie manuelle. C'est le mode par défaut en dev tant que la clé n'est pas posée.
 */
import type { ResolvedAddress } from '#shared/types/places'

export const PLACES_BASE = 'https://places.googleapis.com/v1'

// Garde `typeof` défensif : si la clé n'est pas (encore) dans le runtimeConfig — config pas rechargée,
// ou absente — on retombe sur vide, donc « non configuré », plutôt que d'appeler Google avec une
// valeur douteuse. Le garde vaut aussi au type : la valeur est ramenée à `string` quoi qu'il arrive.
export const placesApiKey = (): string => {
  const key = useRuntimeConfig().googlePlacesApiKey

  return 'string' === typeof key ? key : ''
}

export const placesConfigured = (): boolean => '' !== placesApiKey()

/**
 * Trace un échec d'appel Places. SANS ÇA, un 403 (clé restreinte par IP, quota dépassé, API non
 * activée) est indiscernable côté client d'un banal « aucun résultat » — et se diagnostique à
 * l'aveugle. On journalise le message ET le corps d'erreur renvoyé par Google, qui nomme la cause
 * exacte. La clé n'y figure jamais : elle ne voyage que dans l'en-tête de la requête.
 */
export const logPlacesFailure = (operation: string, error: unknown): void => {
  const detail = error as { message?: string; data?: unknown }

  console.error(`[places] ${operation} a échoué :`, detail?.message ?? error, detail?.data ?? '')
}

// Forme (partielle) d'un composant d'adresse Places API New. `shortText` porte le code pays ISO-2.
interface AddressComponent {
  types: string[]
  longText?: string
  shortText?: string
}

const componentText = (
  components: readonly AddressComponent[],
  type: string,
  key: 'longText' | 'shortText',
): string => components.find((component) => component.types.includes(type))?.[key] ?? ''

/**
 * Projette une fiche Places sur nos trois champs.
 *
 * `city` : `locality` d'abord, puis `postal_town` (Royaume-Uni) et `administrative_area_level_2` en
 * secours — toutes les fiches n'ont pas de `locality`. `country` : le `shortText` du composant
 * `country`, déjà en ISO-2 (le contrat exige exactement 2 lettres). `billingAddress` : l'adresse
 * formatée telle quelle, une chaîne libre côté API.
 */
export const mapAddress = (place: {
  formattedAddress?: string
  addressComponents?: readonly AddressComponent[]
}): ResolvedAddress => {
  const components = place.addressComponents ?? []

  return {
    city:
      componentText(components, 'locality', 'longText') ||
      componentText(components, 'postal_town', 'longText') ||
      componentText(components, 'administrative_area_level_2', 'longText'),
    country: componentText(components, 'country', 'shortText').toUpperCase(),
    billingAddress: place.formattedAddress ?? '',
  }
}
