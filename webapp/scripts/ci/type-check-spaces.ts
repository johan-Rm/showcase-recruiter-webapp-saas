/**
 * Type-check de la webapp — UN PASSAGE PAR ESPACE.
 *
 * Une build ne contient qu'un espace (Nuxt Layers) : les auto-imports d'une layer n'existent que
 * dans le contexte de SON espace. Un `vue-tsc` unique vérifie donc les fichiers des quatre espaces
 * avec le contexte d'un seul — et signale comme inconnus les composables des trois autres.
 *
 * `vue-tsc --build` (et non `--noEmit`) : le tsconfig racine est en `files: []` et délègue à des
 * références de projet, que `--noEmit` ne suit pas — il vérifierait zéro fichier.
 */
import { $ } from 'bun'

import { SPACES } from '../../shared/utils/space'

let failed = false

for (const space of SPACES) {
  process.stdout.write(`\n→ type-check : espace ${space}\n`)

  const env = { ...process.env, SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE: space }

  await $`nuxt prepare`.env(env).quiet()
  const check = await $`vue-tsc --build --force`.env(env).nothrow()

  if (check.exitCode !== 0) {
    failed = true
  }
}

process.exit(failed ? 1 : 0)
