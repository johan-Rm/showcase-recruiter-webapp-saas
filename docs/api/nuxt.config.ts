export default defineNuxtConfig({
  extends: ['docus'],
  i18n: false,
  site: {
    name: 'Shortlist API',
    url: process.env.NUXT_SITE_URL ?? 'https://swagger.showcase-recruiter-webapp-saas.example',
  },
  llms: {
    domain: process.env.NUXT_SITE_URL ?? 'https://swagger.showcase-recruiter-webapp-saas.example',
    title: 'Shortlist API',
    description: 'Documentation API showcase-recruiter-webapp-saas.',
  },
  app: {
    head: {
      link: [{ rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' }],
      htmlAttrs: {
        lang: 'fr',
      },
    },
  },
})
