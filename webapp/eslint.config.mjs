// @ts-check
import withNuxt from './.nuxt/eslint.config.mjs'

export default withNuxt(
  {
    // Types générés depuis l'OpenAPI — jamais édités ni lintés à la main.
    ignores: ['shared/types/api.ts', 'shared/types/organization-constraints.ts'],
  },
  {
    // Pages, layouts ET composants des layers d'espace : @nuxt/eslint n'exempte que ceux de la
    // layer active au lint (SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE, `public` par défaut) — on exempte les autres.
    //
    // Pour les pages et layouts, le nommage mono-mot est légitime (`cockpit`, comme
    // `default` / `auth`). Pour les composants, c'est un FAUX POSITIF : Nuxt préfixe par le
    // dossier, donc `registration/Field.vue` s'appelle `RegistrationField` — déjà multi-mot.
    // ESLint ne le voit pas parce que la layer n'est pas active, et ne juge que le fichier.
    files: [
      'spaces/*/app/pages/**/*.vue',
      'spaces/*/app/layouts/**/*.vue',
      'spaces/*/app/components/**/*.vue',
      'base/app/pages/**/*.vue',
      'base/app/layouts/**/*.vue',
    ],
    rules: {
      'vue/multi-word-component-names': 'off',
    },
  },
  {
    rules: {
      // Gouvernance TS/JS (~/.claude/CLAUDE.md) : pas de any non justifié, pas de console en prod.
      '@typescript-eslint/no-explicit-any': 'error',
      'no-console': ['error', { allow: ['warn', 'error'] }],
      // Aligné sur Prettier (quality-format-check) : void elements auto-fermés (<input />).
      'vue/html-self-closing': ['warn', { html: { void: 'always' } }],
      // Alias harmonisés (tâche 035) : `~/` pour app/, `#shared` pour shared/ —
      // pas de variantes `@/`, `~~/`, `@@/` qui pointent au même endroit.
      'no-restricted-imports': [
        'error',
        {
          patterns: [
            {
              group: ['@/*', '~~/*', '@@/*'],
              message: 'Utiliser ~/ (app/) ou #shared (shared/) — alias harmonisés.',
            },
          ],
        },
      ],
    },
  },
)
