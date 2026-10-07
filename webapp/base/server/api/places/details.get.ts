import { ofetch } from 'ofetch'
import { z } from 'zod'

import {
  logPlacesFailure,
  mapAddress,
  PLACES_BASE,
  placesApiKey,
  placesConfigured,
} from '../../utils/places'

/**
 * Place Details (Google Places API New), proxifié : résout une suggestion choisie en adresse
 * structurée (ville + pays ISO-2 + adresse formatée pour `billingAddress`).
 *
 * `X-Goog-FieldMask` RÉDUIT aux seuls champs projetés : Places facture selon les champs demandés,
 * réclamer toute la fiche coûterait pour rien. Le `sessionToken` clôt la session ouverte à
 * l'autocomplétion — c'est ce qui la fait facturer comme un tout et non requête par requête.
 */
const querySchema = z.object({
  id: z.string().min(1).max(512),
  session: z.string().min(1).max(64),
})

export default defineEventHandler(async (event) => {
  const { id, session } = await getValidatedQuery(event, querySchema.parse)

  if (!placesConfigured()) {
    throw createError({ statusCode: 503, statusMessage: 'Autocomplétion d’adresse non configurée' })
  }

  try {
    const place = await ofetch(`${PLACES_BASE}/places/${encodeURIComponent(id)}`, {
      headers: {
        'X-Goog-Api-Key': placesApiKey(),
        'X-Goog-FieldMask': 'formattedAddress,addressComponents',
      },
      query: { sessionToken: session },
    })

    return mapAddress(place)
  } catch (error) {
    logPlacesFailure('details', error)

    throw createError({ statusCode: 502, statusMessage: 'Adresse introuvable' })
  }
})
