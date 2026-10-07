import type { Organization, OrganizationReviewCard } from '../../shared/types/organization'

/**
 * Fiche entreprise (DTO API) → carte de revue prête à rendre.
 *
 * Métier pur, zéro framework : le template ne doit ni concaténer, ni deviner ce qu'il manque. Les champs optionnels du DTO (tout l'est, l'OpenAPI ne
 * distingue pas) sont résolus ICI — c'est la frontière, pas l'affichage, qui absorbe le flou.
 */
export const toReviewCard = (organization: Organization): OrganizationReviewCard => {
  return {
    id: organization.id ?? '',
    name: organization.name ?? '',
    legalName: organization.legalName ?? null,
    taxId: organization.taxId ?? null,
    // Ville et pays se lisent d'un bloc : « Casablanca, MA », ou l'un des deux, ou rien.
    location: [organization.city, organization.country].filter(Boolean).join(', ') || null,
    website: organization.website ?? null,
    sector: organization.sector ?? null,
    description: organization.description ?? null,
    acquisitionChannel: organization.acquisitionChannel ?? null,
    acquisitionChannelDetail: organization.acquisitionChannelDetail ?? null,
    // Le contrat garantit le statut : le `?? ''` d'avant inventait une valeur hors énumération.
    validationStatus: organization.validationStatus ?? 'EN_REVUE',
  }
}
