export default defineNuxtConfig({
  extends: ['docus'],
  i18n: false,
  site: {
    name: 'Shortlist Dev Book',
    url: process.env.NUXT_SITE_URL ?? 'https://dev-book.showcase-recruiter-webapp-saas.example',
  },
  llms: {
    domain: process.env.NUXT_SITE_URL ?? 'https://dev-book.showcase-recruiter-webapp-saas.example',
    title: 'Shortlist Dev Book',
    description: 'Cadrage produit, tâches et runbooks showcase-recruiter-webapp-saas.',
  },
  // Le site est servi en STATIQUE (cf. docs/Caddyfile.static) : une route non prérendue n'existe
  // pas en production, elle retombe sur la page d'accueil. Ce qui est ignoré ici est donc
  // volontairement absent du site publié, pas seulement absent du build.
  nitro: {
    prerender: {
      ignore: [
        '/runbooks/',
        // Les cartes de tâches restent hors du site — mais `journal/` est un sous-dossier de
        // `tasks/`, et un motif chaîne les exclurait avec elles (préfixe simple). D'où la
        // fonction : elle rend la seule exception au préfixe exprimable.
        (path: string) => path.startsWith('/tasks/') && !path.startsWith('/tasks/journal'),
      ],
    },
  },
  app: {
    head: {
      htmlAttrs: {
        lang: 'fr',
      },
    },
  },
})
