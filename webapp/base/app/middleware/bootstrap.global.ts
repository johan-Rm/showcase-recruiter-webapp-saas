/**
 * Navigation client : attend que le critique de la route cible soit en store AVANT d'afficher la
 * page — rendu d'un bloc, jamais de rendu partiel (table qui apparaît avant ses agrégats).
 *
 * Instantané si la donnée est déjà là (preload `preloadAll` / visite précédente) : `loadRouteCritical`
 * est alors un no-op. Au SSR le critique initial est résolu par `initCoreData` (app.vue), pas ici.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  if (import.meta.server) return

  await useAppBootstrap().loadRouteCritical(to.name, to.params, to.query)
})
