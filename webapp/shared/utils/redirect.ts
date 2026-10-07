// Cible de redirection après login : la query `?redirect=…` porte le chemin visé avant le rebond
// vers /login. Valeur d'origine externe (URL tapée, lien d'e-mail, onglet rouvert) — jamais suivie
// telle quelle. Pur (aucun import framework), testable sans runtime Nuxt.

/**
 * Assainit une cible de redirection. N'accepte qu'un unique chemin **interne** : commence par `/`,
 * sans `//` ni `/\` (URL protocol-relative, une sortie de domaine déguisée en chemin). Tout le
 * reste → `null`, que le caller replie sur `/`.
 *
 * C'est la protection contre l'open redirect, et c'est ce qui interdit de sauter d'un espace à
 * l'autre : chaque espace est un host distinct, une cible cross-espace est forcément absolue, donc
 * rejetée. Un schéma (`http:`, `javascript:`) ou un hôte ne commençant pas par `/` tombe d'office.
 *
 * Une valeur multiple (`?redirect=a&redirect=b`, rendue en tableau par Vue Router) n'est pas une
 * `string` : ambiguë, donc rejetée.
 */
export const safeRedirectTarget = (value: unknown): string | null => {
  if (typeof value !== 'string' || !value.startsWith('/')) {
    return null
  }

  if (value.startsWith('//') || value.startsWith('/\\')) {
    return null
  }

  return value
}
