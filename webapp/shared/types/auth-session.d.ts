// Forme de la session BFF (nuxt-auth-utils).
// Dans `shared/` : c'est le seul répertoire dont les `.d.ts` sont inclus par les projets TS app ET
// serveur (cf. .nuxt/tsconfig.*.json). À la racine, la déclaration n'était vue que du client — et
// le serveur, qui la consomme, ne la voyait pas.
declare module '#auth-utils' {
  interface User {
    email: string
    roles: string[]
  }

  interface SecureSessionData {
    /** JWT API (Bearer) — jamais exposé au client. */
    apiToken: string
    /**
     * Refresh token Gesdinet — jamais exposé au client.
     *
     * **Optionnel** : le magic link n'en délivre pas. Une session ouverte par lien vit le temps du
     * JWT ; le typer obligatoire mentait sur ce chemin (cf. auth/magic-link/consume).
     */
    apiRefreshToken?: string
  }
}

export {}
