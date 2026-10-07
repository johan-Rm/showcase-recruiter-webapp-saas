/** Fournisseurs d'identité sociale acceptés. Liste FERMÉE, miroir de l'enum côté API. */
export const SOCIAL_PROVIDERS = ['google', 'linkedin'] as const

export type SocialProvider = (typeof SOCIAL_PROVIDERS)[number]

export const isSocialProvider = (value: string): value is SocialProvider =>
  (SOCIAL_PROVIDERS as readonly string[]).includes(value)

/**
 * Quels fournisseurs sont utilisables, un par un. La dégradation est PAR FOURNISSEUR et non globale :
 * Google configuré seul doit afficher son bouton sans attendre LinkedIn.
 */
export type SocialProviderAvailability = Record<SocialProvider, boolean>
