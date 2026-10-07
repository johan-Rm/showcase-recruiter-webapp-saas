import { SOCIAL_PROVIDERS, type SocialProviderAvailability } from '#shared/types/social'

/**
 * Dit au client QUELS fournisseurs sont utilisables, un par un. La dégradation est par fournisseur et
 * non globale : Google configuré seul affiche son bouton sans attendre LinkedIn.
 *
 * Ne renvoie que des booléens — jamais les identifiants eux-mêmes.
 */
export default defineEventHandler(
  (): SocialProviderAvailability =>
    Object.fromEntries(
      SOCIAL_PROVIDERS.map((provider) => [provider, socialConfigured(provider)]),
    ) as SocialProviderAvailability,
)
