import { defineConfig } from 'vitest/config'

// Tests colocalisés `*.vitest.ts`. Périmètre : le code PUR, où qu'il vive — `shared/`, `services/`,
// et depuis ADM-011 les `utils/` d'un espace, quand une fonction extraite d'un composable se teste
// sans monter quoi que ce soit. Le critère n'est pas le dossier, c'est l'absence de runtime Nuxt :
// tant qu'il tient, @nuxt/test-utils reste inutile.
export default defineConfig({
  test: {
    include: ['**/*.vitest.ts'],
    exclude: ['node_modules/**', '.nuxt/**', '.output/**'],
  },
})
