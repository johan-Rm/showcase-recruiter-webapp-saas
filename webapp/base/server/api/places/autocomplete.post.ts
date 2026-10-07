import { ofetch } from 'ofetch'
import { z } from 'zod'

import { logPlacesFailure, PLACES_BASE, placesApiKey, placesConfigured } from '../../utils/places'

import type { PlaceSuggestion } from '#shared/types/places'

/**
 * Autocomplétion d'adresse (Google Places API New), proxifiée : la clé reste côté serveur.
 *
 * Le `sessionToken` — un UUID forgé par le client au début d'une saisie et rejoué jusqu'à la
 * résolution — regroupe l'autocomplétion et le Place Details final en UNE session facturée. Sans lui,
 * chaque frappe se facturerait à l'unité.
 *
 * Sans clé configurée : réponse propre `{ configured: false }`, pas d'erreur — le formulaire bascule
 * alors en saisie manuelle (ville + pays).
 */
const bodySchema = z.object({
  input: z.string().min(1).max(200),
  sessionToken: z.string().min(1).max(64),
})

interface AutocompleteResponse {
  suggestions?: Array<{
    placePrediction?: {
      placeId?: string
      text?: { text?: string }
    }
  }>
}

export default defineEventHandler(async (event) => {
  const { input, sessionToken } = await readValidatedBody(event, bodySchema.parse)

  if (!placesConfigured()) {
    return { configured: false, suggestions: [] as PlaceSuggestion[] }
  }

  try {
    const response = await ofetch<AutocompleteResponse>(`${PLACES_BASE}/places:autocomplete`, {
      method: 'POST',
      headers: { 'X-Goog-Api-Key': placesApiKey() },
      // `languageCode` fixé au français, langue première du produit : le libellé des suggestions suit.
      body: { input, sessionToken, languageCode: 'fr' },
    })

    const suggestions: PlaceSuggestion[] = (response.suggestions ?? [])
      .map((suggestion) => suggestion.placePrediction)
      .filter((prediction) => prediction?.placeId && prediction.text?.text)
      .map((prediction) => ({ id: prediction!.placeId!, label: prediction!.text!.text! }))

    return { configured: true, suggestions }
  } catch (error) {
    // Une erreur Google (clé restreinte, quota, entrée rejetée) ne casse pas la saisie : liste vide,
    // l'utilisateur continue de taper et garde le secours « saisir manuellement ». Elle est en
    // revanche TRACÉE côté serveur, sans quoi un refus de clé se présente exactement comme un
    // « aucun résultat ».
    logPlacesFailure('autocomplete', error)

    return { configured: true, suggestions: [] as PlaceSuggestion[] }
  }
})
