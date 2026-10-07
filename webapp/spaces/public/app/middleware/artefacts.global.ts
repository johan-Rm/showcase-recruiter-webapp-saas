import { isArtefactRouteServed } from '#shared/utils/artefacts'

/**
 * Les maquettes de `pages/artefacts/` n'existent qu'en développement et en preprod : ce sont des
 * artefacts de conception, pas des pages du site.
 *
 * Global et par préfixe d'URL, plutôt qu'un middleware nommé déclaré page par page : une maquette
 * ajoutée plus tard est protégée sans rien avoir à déclarer. Un oubli ici ne se verrait qu'en
 * production, où il n'y a plus de filet.
 *
 * La décision elle-même vit dans `#shared/utils/artefacts` — pure, donc testée sans runtime Nuxt.
 *
 * Cette garde double le `basic_auth` du proxy sans le remplacer : celui-ci protège l'accès en
 * preprod, celle-ci décide de l'existence même des routes.
 */
export default defineNuxtRouteMiddleware((to) => {
  if (isArtefactRouteServed(to.path, Boolean(useRuntimeConfig().public.artefacts))) {
    return
  }

  throw createError({ statusCode: 404, statusMessage: 'Not Found' })
})
