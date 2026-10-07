import { ofetch } from 'ofetch'

import type { AppConfig } from '#shared/types/config'

/**
 * Valeurs métier publiques du produit : coût d'un dévoilé, crédits offerts, locales, pagination,
 * nomenclatures fermées.
 *
 * C'est le PALIER MÉTADONNÉES du webapp — le premier appel, celui dont les écrans suivants
 * dépendent. Relais direct de GET /v1/config, sans session ni Bearer : rien ici n'est propre à un
 * utilisateur, et l'exiger retarderait le chargement le plus précoce du démarrage.
 *
 * `ofetch` et non `$fetch` : ce dernier est typé sur les routes internes de Nitro et son inférence
 * explose sur une URL externe.
 */
export default defineEventHandler(async () => {
  return await ofetch<AppConfig>('/v1/config', {
    baseURL: apiBaseUrl(),
    method: 'GET',
  })
})
