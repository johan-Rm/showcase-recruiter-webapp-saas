import { defineNuxtConfig } from 'nuxt/config'

/**
 * Public Website : le socle tel quel, à une exception près — les maquettes de `pages/artefacts/`.
 */
export default defineNuxtConfig({
  $meta: { name: 'space-public' },

  runtimeConfig: {
    public: {
      // NUXT_PUBLIC_ARTEFACTS : ouvre les routes `/artefacts/*` (maquettes de conception).
      // Défini en dev et en preprod, ABSENT en production — c'est son absence qui les ferme.
      // Un drapeau de runtime et non `import.meta.dev` : la preprod builde en production, où
      // ce dernier vaut `false` et fermerait les maquettes là où on veut les montrer.
      artefacts: false,
    },
  },
})
