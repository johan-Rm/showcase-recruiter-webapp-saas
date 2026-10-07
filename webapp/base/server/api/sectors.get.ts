import { ofetch } from 'ofetch'

/**
 * Référentiel des secteurs d'activité (liste fermée) pour le dropdown d'inscription entreprise.
 *
 * Public : le formulaire d'inscription n'est pas authentifié. Relais direct de GET /v1/sectors, sans
 * session ni Bearer — comme le magic link. `ofetch` et non `$fetch` : ce dernier est typé sur les
 * routes internes de Nitro et son inférence explose sur une URL externe.
 */
export default defineEventHandler(async () => {
  return await ofetch<string[]>('/v1/sectors', {
    baseURL: apiBaseUrl(),
    method: 'GET',
  })
})
