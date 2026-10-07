import { defineNuxtConfig } from 'nuxt/config'

/**
 * Espace entreprise : la layer la plus complète du PoC — shadow de la home,
 * page exclusive (template-le-vivier), shadow d'un composant auto-importé et d'une
 * route serveur du socle.
 */
export default defineNuxtConfig({
  $meta: { name: 'space-entreprise' },
})
