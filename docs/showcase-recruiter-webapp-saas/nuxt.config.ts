export default defineNuxtConfig({
  extends: ['docus'],
  i18n: false,
  site: {
    name: 'Shortlist Docs',
    url: process.env.NUXT_SITE_URL ?? 'https://docs.showcase-recruiter-webapp-saas.example',
  },
  llms: {
    domain: process.env.NUXT_SITE_URL ?? 'https://docs.showcase-recruiter-webapp-saas.example',
    title: 'Shortlist Docs',
    description: 'Documentation technique et produit du MVP Shortlist.',
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
