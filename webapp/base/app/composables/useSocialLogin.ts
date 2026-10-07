import { SOCIAL_PROVIDERS, type SocialProvider } from '#shared/types/social'

/**
 * Fournisseurs sociaux réellement proposés, résolu au rendu SSR : les boutons sont donc justes au
 * PREMIER affichage, sans apparaître après coup. La liste est filtrée côté serveur (identifiants
 * posés ou non) — un fournisseur non configuré n'existe tout simplement pas pour le client.
 *
 * Aucun appel au fournisseur ici : chaque bouton est un simple lien vers le BFF, qui mène la danse
 * OAuth. C'est ce qui garde le `client_secret` hors du navigateur et la page sans script tiers.
 */
export const useSocialLogin = () => {
  // Type EXPLICITE (cf. usePlaces) : coupe l'inférence de route Nitro qui explose côté admin (TS2321).
  const { data } = useFetch<Record<SocialProvider, boolean>>('/api/auth/social/status')

  const providers = computed<SocialProvider[]>(() =>
    SOCIAL_PROVIDERS.filter((provider) => data.value?.[provider] ?? false),
  )

  /** Point d'entrée du BFF : une navigation pleine page, pas un `fetch` — c'est une redirection OAuth. */
  const startPath = (provider: SocialProvider): string => `/api/auth/social/${provider}/redirect`

  return { providers, startPath }
}
