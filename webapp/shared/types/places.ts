/**
 * Contrats de l'autocomplétion d'adresse (Google Places, proxifiée par le BFF), partagés entre le
 * serveur (server/api/places/*) qui les produit et le client (usePlaces) qui les consomme.
 */

/** Une suggestion : un identifiant opaque Places et son libellé affichable. */
export interface PlaceSuggestion {
  id: string
  label: string
}

/** Adresse résolue, projetée sur le contrat Organization. `country` en ISO-2 (exactement 2 lettres). */
export interface ResolvedAddress {
  city: string
  country: string
  billingAddress: string
}
