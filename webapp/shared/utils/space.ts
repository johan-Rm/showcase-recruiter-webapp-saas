/**
 * Espaces applicatifs showcase-recruiter-webapp-saas, en architecture layers.
 * L'espace n'est plus résolu par host à chaque requête : il est figé au
 * build/démarrage via SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE, chaque host étant servi par sa build
 * (Caddy route host → service). Partagé app + server + nuxt.config racine.
 */
export const SPACES = ['public', 'candidat', 'entreprise', 'admin'] as const

export type Space = (typeof SPACES)[number]

/**
 * Rôle minimal exigé par espace SaaS (préfixe — OWNER et MEMBER passent).
 * Consommé par le middleware d'auth du socle.
 */
export const SPACE_ROLE_PREFIX: Record<Exclude<Space, 'public'>, string> = {
  candidat: 'ROLE_CANDIDATE',
  entreprise: 'ROLE_COMPANY_',
  admin: 'ROLE_ADMIN_',
}

/**
 * Espaces ouvrant un tunnel d'inscription : ailleurs, un candidat est invité par
 * lien magique et un admin est créé en interne. Consommé par le login du socle.
 */
export const SPACES_WITH_REGISTRATION: readonly Space[] = ['entreprise']
