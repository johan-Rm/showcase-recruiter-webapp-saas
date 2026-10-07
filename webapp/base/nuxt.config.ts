import { defineNuxtConfig } from 'nuxt/config'

import { FALLBACK_LOCALE, SUPPORTED_LOCALES } from '../shared/utils/locale'

/**
 * Layer socle : commun aux 4 espaces. Les layers spaces/* la surchargent
 * fichier par fichier.
 */
export default defineNuxtConfig({
  $meta: { name: 'base' },

  modules: [
    '@nuxt/eslint',
    '@nuxt/hints',
    '@nuxt/icon',
    '@nuxt/ui',
    '@nuxtjs/color-mode',
    '@nuxtjs/device',
    '@nuxtjs/i18n',
    '@pinia/nuxt',
    '@vueuse/nuxt',
    'nuxt-auth-utils',
  ],

  // Composants Nuxt UI préfixés `U` — ils cohabitent avec les `Ui*` du kit maison
  // (`components/ui/`), remplacés au fil des écrans touchés, jamais en masse. Ses options vivent
  // dans le nuxt.config RACINE (cf. `icon`), dont le projet TS charge les augmentations de type.

  typescript: {
    strict: true,
    // Le type-check bloquant tourne en CI / pré-commit (bun run quality-type-check).
    typeCheck: false,
  },

  runtimeConfig: {
    // Session BFF (nuxt-auth-utils) — surchargées par NUXT_SESSION_*.
    session: {
      // NUXT_SESSION_PASSWORD (≥ 32 caractères) — jamais commité.
      password: '',
      cookie: {
        // NUXT_SESSION_COOKIE_DOMAIN : `.showcase-recruiter-webapp-saas.example` en prod,
        // `.preprod.showcase-recruiter-webapp-saas.example` en preprod, vide en dev (host-only).
        // Scope parent = SSO entre les sous-domaines (cf. architecture.md §5).
        domain: '',
        sameSite: 'lax',
        secure: true,
      },
    },
    // URL de l'API vue du serveur Nuxt (réseau Docker interne en preprod/prod).
    // NUXT_API_INTERNAL_BASE ; retombe sur public.apiBase si vide.
    apiInternalBase: '',
    // Clé Google Places (API New) — SERVEUR uniquement, jamais exposée au client : le BFF proxie
    // l'autocomplétion d'adresse (cf. server/api/places/*). NUXT_GOOGLE_PLACES_API_KEY, jamais
    // commitée. Vide → l'autocomplétion se désactive proprement et le formulaire retombe en saisie
    // manuelle (ville + pays).
    googlePlacesApiKey: '',
    // Clé Claude (Anthropic) — SERVEUR uniquement, jamais exposée au client : le BFF traduit la
    // phrase du recruteur en critères (cf. server/api/ai/parse-search). NUXT_ANTHROPIC_API_KEY,
    // jamais commitée. Vide → la recherche assistée se désactive proprement (le formulaire par
    // critères reste, lui, disponible). En dev la clé n'est pas fournie : la fonctionnalité y est
    // donc muette, ce qui est attendu.
    anthropicApiKey: '',
    // Social login (OIDC) — SERVEUR uniquement. Le `clientSecret` échange le code contre l'id_token
    // et ne doit JAMAIS être exposé : il reste ici, le navigateur ne fait que suivre une redirection.
    // NUXT_GOOGLE_OIDC_CLIENT_ID / _SECRET, NUXT_LINKEDIN_OIDC_CLIENT_ID / _SECRET, jamais commités.
    // Vides → le fournisseur est masqué et le tunnel e-mail reste entier (dégradation par fournisseur).
    googleOidc: {
      clientId: '',
      clientSecret: '',
    },
    linkedinOidc: {
      clientId: '',
      clientSecret: '',
    },
    public: {
      // NUXT_PUBLIC_API_BASE : http://localhost:18082 en dev, https://api.<domaine> ensuite.
      apiBase: 'http://localhost:18082',
      // NUXT_PUBLIC_BASE_DOMAIN : sert aux liens inter-espaces (useSpaceUrl).
      baseDomain: 'localhost',
    },
  },

  // `layoutTransition` vaut `false` par défaut (schéma Nuxt) : sans elle, les seules navigations
  // qui changent de coque — login (`auth`) ↔ espace (`default`) — commutent sèchement, alors même
  // que `pageTransition` est actif. Les deux sont nécessaires pour couvrir toutes les navigations.
  app: {
    // Favicon ici et non dans le `useHead` d'app.vue : celui-ci ne porte que ce qui SUIT la locale
    // ou l'espace. L'icône est la même pour les 4 espaces et ne dépend de rien — config statique.
    head: {
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
      ],
    },
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'layout', mode: 'out-in' },
  },

  // Seul arbitre de la résolution de la locale (URL > cookie > navigateur > défaut).
  // Le catalogue dérive du registre partagé — aucune liste de langues en dur ici.
  i18n: {
    defaultLocale: FALLBACK_LOCALE,
    strategy: 'prefix_except_default',
    locales: SUPPORTED_LOCALES.map(({ code, iso, name, dir, file }) => ({
      code,
      language: iso,
      name,
      dir,
      file,
    })),
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'showcase-recruiter-webapp-saas_locale',
      redirectOn: 'root',
      fallbackLocale: FALLBACK_LOCALE,
    },
  },
})
