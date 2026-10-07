import { fileURLToPath } from 'node:url'

import { SPACES, type Space } from './shared/utils/space'

// Config racine de la webapp showcase-recruiter-webapp-saas — architecture Nuxt Layers (tâche 022 reprise) :
// un socle commun (base/) + une layer par espace (spaces/*), UNE BUILD PAR ESPACE
// sélectionnée par SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE. Chaque host (apex, candidat., entreprise., admin.)
// est servi par sa build. Réf : webapp/README.md · architecture.md §5.
//
// ⚠️ Les layers d'espace vivent dans `spaces/`, PAS dans `layers/` : tout sous-dossier
// de `layers/` serait auto-enregistré et les 4 espaces actifs en même temps.
// Priorité `extends` : la PREMIÈRE entrée gagne → l'espace surcharge la base.
const space: Space = SPACES.includes(process.env.SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE as Space)
  ? (process.env.SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE as Space)
  : 'public'

const servicesDir = fileURLToPath(new URL('./services', import.meta.url))

// Posé par docker-compose.dev.yml : dans un conteneur, le bind mount ne relaie pas les événements
// inotify de l'hôte. Sans scrutation, le HMR ne se déclenche jamais et le SSR sert du code périmé.
const watchOptions =
  process.env.DEV_WATCH_POLLING === 'true' ? { usePolling: true, interval: 300 } : undefined

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  extends: [`./spaces/${space}`, './base'],

  // Le chemin absolu reste résolu lorsque Nuxt déplace ses imports dans son cache de build.
  css: [fileURLToPath(new URL('./base/app/assets/css/main.css', import.meta.url))],

  // `services/` vit à la racine (hors layers, comme `shared/`) : sans alias, il ne serait
  // joignable que par `~~/`, interdit par la règle d'alias harmonisés (tâche 035).
  // Déclaré aux TROIS résolveurs : `alias` (types + app), Vite (client/SSR) et Nitro (BFF).
  // Le seul `alias` de Nuxt ne suffit pas — Vite lit `#…` comme un import interne de paquet.
  alias: {
    '#services': servicesDir,
  },

  // Le projet TS `app` inclut `../**/*` : sans exclusion, il vérifierait les fichiers des TROIS
  // autres espaces avec le contexte de CELUI-CI — leurs auto-imports de layer n'y existent pas, et
  // ils seraient signalés inconnus. Chaque espace est vérifié dans SA passe (scripts/ci/type-check-spaces).
  typescript: {
    tsConfig: {
      exclude: SPACES.filter((candidate) => candidate !== space).map(
        (inactive) => `../spaces/${inactive}`,
      ),
    },
  },

  // Options de module : ici et pas dans base/, dont le nuxt.config est vérifié par un projet TS qui
  // ne charge pas les augmentations de `NuxtConfig` (la clé y serait signalée inconnue).
  // serverBundle local = icônes Lucide servies sans CDN Iconify au runtime.
  icon: {
    serverBundle: {
      collections: ['lucide'],
    },
  },

  // `fonts: false` : Nuxt UI installerait sinon `@nuxt/fonts`, qui résoudrait Sora et Geist chez un
  // fournisseur distant alors qu'elles sont auto-hébergées (`base/app/assets/css/fonts.css`) — le
  // build dépendrait du réseau pour des polices déjà présentes.
  ui: {
    fonts: false,
  },

  // LE CURSEUR DES BOUTONS. Tailwind v4 a retiré le `cursor: pointer` que le navigateur posait
  // historiquement sur `<button>` : un bouton Nuxt UI rend donc la flèche, et rien à l'écran ne dit
  // plus qu'il se clique. Posé sur le SLOT `base`, il s'ajoute aux classes du thème plutôt que de
  // les remplacer, et `disabled:cursor-not-allowed` continue de le supplanter — un bouton désactivé
  // ne doit pas promettre un clic.
  //
  // ICI ET PAS DANS UN `app.config.ts` DE LAYER : `base/app/app.config.ts` n'est pas transformé au
  // démarrage à froid, et son `defineAppConfig` part alors en `ReferenceError` qui met TOUTES les
  // pages en 500. Le HMR le masque — le fichier passe tant que le serveur ne redémarre pas.
  appConfig: {
    ui: {
      button: {
        slots: {
          base: 'cursor-pointer',
        },
        // L'ICÔNE NE DOMINE PAS SON TEXTE. La bibliothèque la dimensionne indépendamment du mot
        // qu'elle accompagne, et toujours plus grand — vingt pixels pour un texte de quatorze, seize
        // pour un texte de douze. L'œil se pose alors sur le signe, qui ne fait que redire ce que le
        // mot dit déjà.
        //
        // UN CRAN EN DESSOUS, ET NON À ÉGALITÉ : à taille égale une icône paraît plus grosse qu'une
        // lettre, son glyphe occupant tout son carré là où une minuscule n'en remplit que la moitié.
        // « Pas plus grande que le texte » se traduit donc par environ quatre cinquièmes.
        //
        // La roue de chargement suit, sans quoi le bouton changerait de largeur au clic.
        variants: {
          size: {
            xs: { leadingIcon: 'size-2.5', trailingIcon: 'size-2.5', loadingIcon: 'size-2.5' },
            sm: { leadingIcon: 'size-2.5', trailingIcon: 'size-2.5', loadingIcon: 'size-2.5' },
            md: { leadingIcon: 'size-3', trailingIcon: 'size-3', loadingIcon: 'size-3' },
            lg: { leadingIcon: 'size-3', trailingIcon: 'size-3', loadingIcon: 'size-3' },
            xl: { leadingIcon: 'size-3.5', trailingIcon: 'size-3.5', loadingIcon: 'size-3.5' },
          },
        },
        // UN BOUTON À ICÔNE SEULE GARDE SA TAILLE. La règle énonce un RAPPORT entre une icône et son
        // texte ; sans texte il n'y a pas de rapport, et l'icône devient le seul contenu du bouton —
        // la réduire rendrait la cible plus difficile à viser pour rien.
        compoundVariants: [
          { square: true, size: 'xs', class: { leadingIcon: 'size-4', trailingIcon: 'size-4' } },
          { square: true, size: 'sm', class: { leadingIcon: 'size-4', trailingIcon: 'size-4' } },
          { square: true, size: 'md', class: { leadingIcon: 'size-5', trailingIcon: 'size-5' } },
          { square: true, size: 'lg', class: { leadingIcon: 'size-5', trailingIcon: 'size-5' } },
          { square: true, size: 'xl', class: { leadingIcon: 'size-6', trailingIcon: 'size-6' } },
        ],
      },
    },
  },

  colorMode: {
    classSuffix: '',
    preference: 'system',
    fallback: 'light',
  },

  // Vite revalide les modules (HMR + rendu SSR), chokidar détecte les fichiers ajoutés ou
  // supprimés (pages, layouts) : les deux watchers doivent scruter, sinon l'un des deux reste sourd.
  watchers: {
    chokidar: watchOptions,
  },

  // Le plugin Vite de Tailwind n'est PAS déclaré ici : `@nuxt/ui` le pousse lui-même (hook
  // `vite:extend`). Deux instances traiteraient la même feuille deux fois.
  vite: {
    resolve: {
      alias: { '#services': servicesDir },
    },
    server: {
      watch: watchOptions,
    },
  },

  nitro: {
    alias: { '#services': servicesDir },
  },

  future: {
    compatibilityVersion: 5,
  },

  runtimeConfig: {
    public: {
      space,
    },
  },
})
