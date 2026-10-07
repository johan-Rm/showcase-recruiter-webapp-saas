import type { components } from '#shared/types/api'

/**
 * Fiche entreprise, telle que l'API la renvoie. `Organization.CollectionOutput` (généré) type ses
 * `items` en `unknown` — l'enveloppe de collection est générique côté API et l'OpenAPI ne porte pas
 * le paramètre de type. On rétablit ici le type réel des éléments, une seule fois.
 */
export type Organization = components['schemas']['Organization']

export interface OrganizationReviewQueue {
  items: Organization[]
  total: number
  page: number
  perPage: number
}

/**
 * Entreprise telle que l'écran de revue admin la consomme : champs résolus, libellés déjà cherchés,
 * localisation déjà composée. Le DTO d'API n'entre pas dans les composants (cf. services/organization).
 */
export interface OrganizationReviewCard {
  id: string
  name: string
  legalName: string | null
  taxId: string | null
  location: string | null
  website: string | null
  sector: string | null
  description: string | null
  acquisitionChannel: AcquisitionChannel | null
  acquisitionChannelDetail: string | null
  validationStatus: OrganizationValidationStatus
}

type ApiAcquisitionChannel = NonNullable<
  components['schemas']['Organization.OrganizationInput']['acquisitionChannel']
>

/**
 * Provenance de l'entreprise, figée à l'inscription (cf. cadrage inscription.md).
 * Liste énumérable au runtime : le select d'inscription l'itère, une union de types ne suffit pas.
 */
export const ACQUISITION_CHANNELS = [
  'RECOMMANDATION',
  'LINKEDIN',
  'RECHERCHE_WEB',
  'SALON',
  'CONTACT_DIRECT',
  'AUTRE',
] as const satisfies readonly ApiAcquisitionChannel[]

/** Ne compile que si `T` est `never`. La contrainte est ce qui échoue — un type conditionnel, lui,
 * se contenterait de valoir `never` sans rien signaler. */
type AssertNever<T extends never> = T

/**
 * Le `satisfies` ci-dessus rejette un canal que l'API ne connaît pas ; il laisse passer un canal que
 * l'API a gagné et que la liste n'a pas suivi. Cette garde ferme l'autre sens : le canal manquant
 * survit à l'`Exclude`, viole la contrainte `extends never`, et casse le type-check.
 */
type _ChannelsExhaustive = AssertNever<
  Exclude<ApiAcquisitionChannel, (typeof ACQUISITION_CHANNELS)[number]>
>

export type AcquisitionChannel = (typeof ACQUISITION_CHANNELS)[number]

/** Tous les statuts que l'API peut rendre — y compris ceux qu'aucun écran ne filtre. */
export type OrganizationValidationStatus = NonNullable<
  components['schemas']['Organization']['validationStatus']
>

/**
 * Statuts **filtrables** dans la file de revue admin : un sous-ensemble ASSUMÉ. `NOUVEAU` et
 * `EMAIL_VERIFIE` sont des états transitoires du tunnel d'inscription — une entreprise n'y séjourne
 * pas, personne ne les traite. D'où l'absence de garde d'exhaustivité ici, contrairement aux canaux
 * d'acquisition : le `satisfies` interdit d'inventer un statut, il n'oblige pas à tous les lister.
 */
export const ORGANIZATION_REVIEW_STATUSES = [
  'EN_REVUE',
  'INCOMPLET',
  'VALIDEE',
  'REFUSE',
] as const satisfies readonly OrganizationValidationStatus[]

export type OrganizationReviewStatus = (typeof ORGANIZATION_REVIEW_STATUSES)[number]

/**
 * Un membre de l'équipe.
 *
 * `id` est celui du RECRUTEUR et non de la personne : c'est la casquette qu'on retire, pas
 * quelqu'un qu'on supprime.
 *
 * Rétabli à la main pour la raison habituelle : l'enveloppe générée type les `items` d'une
 * collection en opaque, l'OpenAPI ne portant pas le paramètre de type.
 */
export interface OrganizationMember {
  id: string
  givenName: string
  familyName: string
  contactEmail: string | null
  organizationRole: string
  jobTitle: string | null
  status: string
}

/**
 * Une invitation en attente.
 *
 * Ce n'est PAS une personne : elle a une adresse et une échéance, pas un nom ni un poste. C'est
 * pourquoi l'écran lui donne sa propre liste.
 */
export interface OrganizationInvitation {
  id: string
  email: string
  role: string
  status: string
  token: string
  invitedById: string
  expiresAt: string
  createdAt: string
}

export interface OrganizationMemberCollection {
  items: OrganizationMember[]
  total: number
  page: number
  perPage: number
}

export interface OrganizationInvitationCollection {
  items: OrganizationInvitation[]
  total: number
  page: number
  perPage: number
}
